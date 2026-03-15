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
        $terminalId = session('terminal_id');

        // Bugünün özet bilgileri — tek sorguda çek
        $salesStats = DB::table('sales')
            ->where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->whereDate('sold_at', $today)
            ->where('status', 'completed')
            ->selectRaw('SUM(grand_total) as total_sales, COUNT(*) as sale_count, SUM(cash_amount) as cash_total, SUM(card_amount) as card_total, AVG(grand_total) as avg_basket')
            ->first();

        $refundTotal = DB::table('sales')
            ->where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->whereDate('sold_at', $today)
            ->where('status', 'refunded')
            ->sum('grand_total');

        $orderStats = DB::table('orders')
            ->where('branch_id', $branchId)
            ->whereDate('ordered_at', $today)
            ->selectRaw("COUNT(*) as order_count, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            ->first();

        $stats = [
            'total_sales'      => $salesStats->total_sales ?? 0,
            'sale_count'       => $salesStats->sale_count ?? 0,
            'cash_total'       => $salesStats->cash_total ?? 0,
            'card_total'       => $salesStats->card_total ?? 0,
            'order_count'      => $orderStats->order_count ?? 0,
            'cancelled_orders' => $orderStats->cancelled_orders ?? 0,
            'refund_total'     => $refundTotal ?? 0,
            'avg_basket'       => $salesStats->avg_basket ?? 0,
        ];

        // Aktif kasa
        $activeRegister = CashRegister::where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'open')
            ->first();

        // Son Z raporları
        $zReports = CashRegister::where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'closed')
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
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'completed')
            ->selectRaw("{$hourExpr}, SUM(grand_total) as total, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        return view('pos.day-operations.index', compact('stats', 'activeRegister', 'zReports', 'hourlySales'));
    }
}
