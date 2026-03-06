<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashRegisterController extends Controller
{
    protected CashRegisterService $service;

    public function __construct(CashRegisterService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $branchId = session('branch_id');
        $register = $this->service->getActiveRegister($branchId);
        
        // Sales stats for current register period
        $stats = ['cash_total' => 0, 'card_total' => 0, 'credit_total' => 0, 'sale_count' => 0];
        if ($register) {
            $salesQuery = Sale::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->where('sold_at', '>=', $register->opened_at);
            $stats['cash_total']   = (clone $salesQuery)->whereIn('payment_method', ['cash', 'mixed'])->sum('cash_amount');
            $stats['card_total']   = (clone $salesQuery)->whereIn('payment_method', ['card', 'mixed'])->sum('card_amount');
            $stats['credit_total'] = (clone $salesQuery)->where('payment_method', 'credit')->sum('grand_total');
            $stats['sale_count']   = $salesQuery->count();
        }
        
        $zReports = CashRegister::where('branch_id', $branchId)
            ->where('status', 'closed')
            ->orderBy('closed_at', 'desc')
            ->limit(10)
            ->with('user')
            ->get();
        
        return view('pos.cash-register.index', compact('register', 'stats', 'zReports'));
    }

    public function open(Request $request)
    {
        try {
            $register = $this->service->openRegister(
                session('branch_id'),
                auth()->id(),
                $request->opening_amount ?? 0,
                $request->notes
            );
            return redirect()->route('pos.cash-register')->with('success', 'Kasa başarıyla açıldı.');
        } catch (\Exception $e) {
            return redirect()->route('pos.cash-register')->with('error', $e->getMessage());
        }
    }

    public function close(Request $request)
    {
        $request->validate(['actual_cash' => 'required|numeric|min:0']);
        
        $active = $this->service->getActiveRegister(session('branch_id'));
        if (!$active) {
            return redirect()->route('pos.cash-register')->with('error', 'Açık kasa bulunamadı.');
        }

        try {
            $register = $this->service->closeRegister(
                $active->id,
                $request->actual_cash,
                $request->notes
            );
            return redirect()->route('pos.cash-register')->with('success', 'Kasa başarıyla kapatıldı.');
        } catch (\Exception $e) {
            return redirect()->route('pos.cash-register')->with('error', $e->getMessage());
        }
    }

    public function report(CashRegister $register)
    {
        $register->load('user');
        
        // Get sales breakdown for this register period
        $salesQuery = Sale::where('branch_id', $register->branch_id)
            ->where('status', 'completed')
            ->where('sold_at', '>=', $register->opened_at);
        
        if ($register->closed_at) {
            $salesQuery->where('sold_at', '<=', $register->closed_at);
        }
        
        $salesByMethod = $salesQuery->get()->groupBy('payment_method');
        
        return response()->json([
            'register' => $register,
            'sales_by_method' => $salesByMethod->map(fn($sales) => [
                'count' => $sales->count(),
                'total' => $sales->sum('grand_total'),
            ]),
        ]);
    }
}
