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
        $query = CashRegister::with('user')
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

        $registers = $query->paginate(30);

        $stats = [
            'total_registers' => CashRegister::count(),
            'total_sales_all' => CashRegister::where('status', 'closed')->sum('total_sales'),
            'total_cash_all' => CashRegister::where('status', 'closed')->sum('total_cash'),
            'total_card_all' => CashRegister::where('status', 'closed')->sum('total_card'),
            'avg_difference' => CashRegister::where('status', 'closed')->avg('difference') ?? 0,
        ];

        return view('pos.cash-report.index', compact('registers', 'stats'));
    }

    public function show(CashRegister $register)
    {
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
