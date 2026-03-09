<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashReportController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $query = CashRegister::with('user')
            ->where('branch_id', $branchId)
            ->orderBy('opened_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('opened_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('opened_at', '<=', $request->end_date);
        }

        $registers = $query->paginate(30)->withQueryString();

        $stats = [
            'total_registers' => CashRegister::where('branch_id', $branchId)->count(),
            'total_sales_all' => CashRegister::where('branch_id', $branchId)->where('status', 'closed')->sum('total_sales'),
            'total_cash_all'  => CashRegister::where('branch_id', $branchId)->where('status', 'closed')->sum('total_cash'),
            'total_card_all'  => CashRegister::where('branch_id', $branchId)->where('status', 'closed')->sum('total_card'),
            'avg_difference'  => CashRegister::where('branch_id', $branchId)->where('status', 'closed')->avg('difference') ?? 0,
            // Veresiye: Sales tablosundan hesapla (cash_register'da sütun yok)
            'total_credit_all' => Sale::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->where('credit_amount', '>', 0)
                ->when($request->filled('start_date'), fn($q) => $q->whereDate('sold_at', '>=', $request->start_date))
                ->when($request->filled('end_date'),   fn($q) => $q->whereDate('sold_at', '<=', $request->end_date))
                ->sum('credit_amount'),
        ];

        // Her kayıt için veresiye toplamını hesapla
        $registerIds = $registers->pluck('id');
        $creditByRegister = [];
        foreach ($registers as $reg) {
            $q = Sale::where('branch_id', $reg->branch_id)
                ->where('status', 'completed')
                ->where('credit_amount', '>', 0)
                ->where('sold_at', '>=', $reg->opened_at);
            if ($reg->closed_at) $q->where('sold_at', '<=', $reg->closed_at);
            $creditByRegister[$reg->id] = $q->sum('credit_amount');
        }

        return view('pos.cash-report.index', compact('registers', 'stats', 'creditByRegister'));
    }

    public function show(CashRegister $register)
    {
        if ($register->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        $register->load('user');

        // Bu kasanın satışları
        $sales = Sale::where('branch_id', $register->branch_id)
            ->whereBetween('sold_at', [$register->opened_at, $register->closed_at ?? now()])
            ->orderBy('sold_at', 'desc')
            ->get();

        return response()->json([
            'register' => $register,
            'sales' => $sales,
        ]);
    }
}
