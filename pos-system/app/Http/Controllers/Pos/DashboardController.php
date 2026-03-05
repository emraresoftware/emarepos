<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CashRegister;
use App\Models\Order;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');
        $today = Carbon::today();
        
        // Today's stats
        $todaySales = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('sold_at', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total), 0) as total, COALESCE(SUM(cash_amount), 0) as cash, COALESCE(SUM(card_amount), 0) as card')
            ->first();
        
        // Active cash register
        $activeRegister = CashRegister::where('branch_id', $branchId)->where('status', 'open')->first();
        
        // Low stock products
        $lowStockCount = Product::whereColumn('stock_quantity', '<=', 'critical_stock')
            ->where('is_active', true)
            ->where('is_service', false)
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
            ->where('status', 'completed')
            ->orderBy('sold_at', 'desc')
            ->limit(10)
            ->get();
        
        // Weekly chart data (last 7 days)
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayTotal = Sale::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereDate('sold_at', $date)
                ->sum('grand_total');
            $weeklyData[] = [
                'date' => $date->format('d.m'),
                'day' => $date->locale('tr')->dayName,
                'total' => (float)$dayTotal,
            ];
        }
        
        return view('pos.dashboard', compact(
            'todaySales', 'activeRegister', 'lowStockCount',
            'activeTables', 'pendingOrders', 'recentSales', 'weeklyData'
        ));
    }
}
