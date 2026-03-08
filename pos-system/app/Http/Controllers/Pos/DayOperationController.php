<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DayOperationController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $branchId = session('branch_id');

        // Bugünün özet bilgileri
        $stats = [
            'total_sales' => Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)->where('status', 'completed')->sum('grand_total'),
            'sale_count' => Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)->where('status', 'completed')->count(),
            'cash_total' => Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)->where('status', 'completed')->where('payment_method', 'cash')->sum('grand_total'),
            'card_total' => Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)->where('status', 'completed')->where('payment_method', 'card')->sum('grand_total'),
            'order_count' => Order::where('branch_id', $branchId)->whereDate('ordered_at', $today)->count(),
            'cancelled_orders' => Order::where('branch_id', $branchId)->whereDate('ordered_at', $today)->where('status', 'cancelled')->count(),
            'refund_total' => Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)->where('status', 'refunded')->sum('grand_total'),
            'avg_basket' => Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)->where('status', 'completed')->avg('grand_total') ?? 0,
        ];

        // Aktif kasa
        $activeRegister = CashRegister::where('branch_id', $branchId)->where('status', 'open')->first();

        // Son Z raporları
        $zReports = CashRegister::where('branch_id', $branchId)->where('status', 'closed')
            ->with('user')
            ->orderBy('closed_at', 'desc')
            ->limit(10)
            ->get();

        // Saatlik satış dağılımı
        $driver = DB::getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "strftime('%H', sold_at) as hour"
            : "LPAD(HOUR(sold_at), 2, '0') as hour";
        
        $hourlySales = Sale::where('branch_id', $branchId)->whereDate('sold_at', $today)
            ->where('status', 'completed')
            ->selectRaw("{$hourExpr}, SUM(grand_total) as total, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        return view('pos.day-operations.index', compact('stats', 'activeRegister', 'zReports', 'hourlySales'));
    }
}
