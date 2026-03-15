<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CashRegister;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');
        $terminalId = session('terminal_id');
        $today = Carbon::today();
        
        // Today's stats
        $todaySales = Sale::where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'completed')
            ->whereDate('sold_at', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total, COALESCE(SUM(cash_amount), 0) as cash, COALESCE(SUM(card_amount), 0) as card')
            ->first();
        
        // Active cash register
        $activeRegister = CashRegister::where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'open')
            ->first();
        
        // Low stock products
        $lowStockCount = Product::where('is_active', true)
            ->where('is_service', false)
            ->with(['branches' => fn ($q) => $q->where('branch_id', $branchId)])
            ->get()
            ->filter(fn ($product) => $product->critical_stock > 0 && $product->stockForBranch($branchId) <= $product->critical_stock)
            ->count();
        
        // Active tables
        $activeTables = \App\Models\RestaurantTable::where('branch_id', $branchId)
            ->where('status', 'occupied')
            ->count();
        
        // Pending kitchen orders
        $pendingOrders = Order::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'preparing'])
            ->count();
        
        // Recent sales (last 10)
        $recentSales = Sale::where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'completed')
            ->orderBy('sold_at', 'desc')
            ->limit(10)
            ->get();
        
        // Weekly chart data (last 7 days) — tek sorguda çek (N+1 önlemi)
        $driver = DB::getDriverName();
        $dateExpr = $driver === 'sqlite' ? "date(sold_at)" : "DATE(sold_at)";
        $weeklyRaw = Sale::where('branch_id', $branchId)
            ->when($terminalId, fn ($q) => $q->where('terminal_id', $terminalId))
            ->where('status', 'completed')
            ->where('sold_at', '>=', Carbon::today()->subDays(6)->startOfDay())
            ->selectRaw("{$dateExpr} as day_date, COALESCE(SUM(grand_total), 0) as total")
            ->groupBy('day_date')
            ->pluck('total', 'day_date')
            ->toArray();

        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $key  = $date->format('Y-m-d');
            $weeklyData[] = [
                'date'  => $date->format('d.m'),
                'day'   => $date->locale('tr')->dayName,
                'total' => (float) ($weeklyRaw[$key] ?? 0),
            ];
        }
        
        return view('pos.dashboard', compact(
            'todaySales', 'activeRegister', 'lowStockCount',
            'activeTables', 'pendingOrders', 'recentSales', 'weeklyData'
        ));
    }
}
