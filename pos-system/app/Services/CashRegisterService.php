<?php
namespace App\Services;

use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashRegisterService
{
    public function openRegister(int $branchId, int $userId, float $openingAmount = 0, ?string $notes = null): CashRegister
    {
        // Check if there's already an open register for this branch
        $existing = CashRegister::where('branch_id', $branchId)
            ->where('status', 'open')
            ->first();
            
        if ($existing) {
            throw new \Exception('Bu şube için zaten açık bir kasa var.');
        }
        
        return CashRegister::create([
            'tenant_id' => session('tenant_id'),
            'branch_id' => $branchId,
            'user_id' => $userId,
            'opening_amount' => $openingAmount,
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'notes' => $notes,
        ]);
    }
    
    public function closeRegister(int $registerId, float $closingAmount, ?string $notes = null): CashRegister
    {
        return DB::transaction(function () use ($registerId, $closingAmount, $notes) {
            $register = CashRegister::findOrFail($registerId);
            
            if ($register->status !== 'open') {
                throw new \Exception('Bu kasa zaten kapalı.');
            }
            
            // Calculate expected amount from sales
            $salesData = Sale::where('branch_id', $register->branch_id)
                ->where('status', 'completed')
                ->where('sold_at', '>=', $register->opened_at)
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(grand_total), 0) as total_sales,
                    COALESCE(SUM(cash_amount), 0) as total_cash,
                    COALESCE(SUM(card_amount), 0) as total_card
                ')
                ->first();
            
            $refundsData = Sale::where('branch_id', $register->branch_id)
                ->whereIn('status', ['refunded', 'cancelled'])
                ->where('updated_at', '>=', $register->opened_at)
                ->selectRaw('COALESCE(SUM(grand_total), 0) as total_refunds, COALESCE(SUM(cash_amount), 0) as cash_refunds')
                ->first();
            
            $expectedAmount = $register->opening_amount + ($salesData->total_cash ?? 0) - ($refundsData->cash_refunds ?? 0);
            
            // Nakit gelir/giderleri dahil et
            $cashIncomes = Income::where('branch_id', $register->branch_id)
                ->where('payment_type', 'cash')
                ->where('date', '>=', $register->opened_at->toDateString())
                ->sum('amount');
            
            $cashExpenses = Expense::where('branch_id', $register->branch_id)
                ->where('payment_type', 'cash')
                ->where('date', '>=', $register->opened_at->toDateString())
                ->sum('amount');
            
            $expectedAmount += $cashIncomes - $cashExpenses;
            
            $register->update([
                'closing_amount' => $closingAmount,
                'expected_amount' => $expectedAmount,
                'difference' => $closingAmount - $expectedAmount,
                'total_sales' => $salesData->total_sales ?? 0,
                'total_cash' => $salesData->total_cash ?? 0,
                'total_card' => $salesData->total_card ?? 0,
                'total_refunds' => $refundsData->total_refunds ?? 0,
                'total_transactions' => $salesData->total_transactions ?? 0,
                'status' => 'closed',
                'closed_at' => Carbon::now(),
                'notes' => $notes,
            ]);
            
            return $register;
        });
    }
    
    public function getActiveRegister(int $branchId): ?CashRegister
    {
        return CashRegister::where('branch_id', $branchId)
            ->where('status', 'open')
            ->first();
    }
}
