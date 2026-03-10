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
        $stats = ['cash_total' => 0, 'card_total' => 0, 'credit_total' => 0, 'transfer_total' => 0, 'total_sales' => 0, 'sale_count' => 0];
        if ($register) {
            $salesQuery = Sale::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->where('sold_at', '>=', $register->opened_at);
            $stats['cash_total']     = (clone $salesQuery)->sum('cash_amount');
            $stats['card_total']     = (clone $salesQuery)->sum('card_amount');
            $stats['transfer_total'] = (clone $salesQuery)->sum('transfer_amount');
            $stats['credit_total']   = (clone $salesQuery)->sum('credit_amount');
            $stats['total_sales']    = (clone $salesQuery)->sum('grand_total');
            $stats['sale_count']     = $salesQuery->count();
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
        if ($register->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
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

    /**
     * Kasa dönemi satış listesi (AJAX)
     * ?type=credit|cash|card|all
     */
    public function salesDetail(Request $request)
    {
        $branchId = session('branch_id');
        $register = $this->service->getActiveRegister($branchId);

        $query = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->with(['customer', 'user'])
            ->orderBy('sold_at', 'desc');

        if ($register) {
            $query->where('sold_at', '>=', $register->opened_at);
        }

        $type = $request->get('type', 'all');
        if ($type === 'credit') {
            $query->where(function ($q) {
                $q->where('payment_method', 'credit')
                  ->orWhere(function ($q2) {
                      $q2->where('payment_method', 'mixed')
                         ->where('credit_amount', '>', 0);
                  });
            });
        } elseif ($type === 'cash') {
            $query->whereIn('payment_method', ['cash', 'mixed']);
        } elseif ($type === 'card') {
            $query->whereIn('payment_method', ['card', 'mixed']);
        }

        $sales = $query->limit(100)->get()->map(fn($s) => [
            'id'             => $s->id,
            'receipt_no'     => $s->receipt_no,
            'sold_at'        => $s->sold_at?->format('d.m.Y H:i'),
            'grand_total'    => $s->grand_total,
            'cash_amount'    => $s->cash_amount,
            'card_amount'    => $s->card_amount,
            'credit_amount'  => $s->credit_amount,
            'payment_method' => $s->payment_method,
            'customer_name'  => $s->customer?->name ?? '—',
            'staff_name'     => $s->staff_name ?? ($s->user?->name ?? '—'),
            'notes'          => $s->notes,
        ]);

        return response()->json(['success' => true, 'sales' => $sales]);
    }

    /**
     * Tek satışın kalem detayı (AJAX)
     */
    public function saleItems(\App\Models\Sale $sale)
    {
        if ($sale->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        $sale->load('items', 'customer', 'user');
        return response()->json([
            'success' => true,
            'sale' => [
                'id'             => $sale->id,
                'receipt_no'     => $sale->receipt_no,
                'sold_at'        => $sale->sold_at?->format('d.m.Y H:i'),
                'grand_total'    => $sale->grand_total,
                'cash_amount'    => $sale->cash_amount,
                'card_amount'    => $sale->card_amount,
                'credit_amount'  => $sale->credit_amount,
                'payment_method' => $sale->payment_method,
                'customer_name'  => $sale->customer?->name ?? '—',
                'staff_name'     => $sale->staff_name ?? ($sale->user?->name ?? '—'),
                'notes'          => $sale->notes,
                'items'          => $sale->items->map(fn($i) => [
                    'product_name' => $i->product_name,
                    'quantity'     => $i->quantity,
                    'unit_price'   => $i->unit_price,
                    'total'        => $i->total,
                ]),
            ],
        ]);
    }
}
