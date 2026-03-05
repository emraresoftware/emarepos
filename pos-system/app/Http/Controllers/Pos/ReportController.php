<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $salesQuery = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalRevenue = (clone $salesQuery)->sum('grand_total');
        $saleCount = (clone $salesQuery)->count();
        $totalVat = (clone $salesQuery)->sum('vat_total');

        $stats = [
            'total_revenue' => $totalRevenue,
            'sale_count' => $saleCount,
            'avg_basket' => $saleCount > 0 ? round($totalRevenue / $saleCount, 2) : 0,
            'total_vat' => $totalVat,
        ];

        // Daily sales for chart
        $dailySales = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('DATE(sold_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Payment method breakdown
        $paymentStats = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('payment_method, SUM(grand_total) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        // Top products
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
        ->select('product_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(total) as total'))
        ->groupBy('product_name')
        ->orderByDesc('total')
        ->limit(10)
        ->get();

        // Category breakdown
        $categoryStats = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
        ->join('products', 'sale_items.product_id', '=', 'products.id')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->select('categories.name', DB::raw('SUM(sale_items.total) as revenue'))
        ->groupBy('categories.name')
        ->orderByDesc('revenue')
        ->get();

        return view('pos.reports.index', compact(
            'stats', 'dailySales', 'paymentStats', 'topProducts', 'categoryStats', 'startDate', 'endDate'
        ));
    }

    public function daily(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $branchId = session('branch_id');
        
        $sales = Sale::where('branch_id', $branchId)
            ->whereDate('sold_at', $date)
            ->where('status', 'completed');
        
        $summary = [
            'date' => $date,
            'total_sales' => $sales->count(),
            'grand_total' => $sales->sum('grand_total'),
            'cash_total' => $sales->clone()->where('payment_method', 'cash')->sum('grand_total'),
            'card_total' => $sales->clone()->where('payment_method', 'card')->sum('grand_total'),
            'credit_total' => $sales->clone()->where('payment_method', 'credit')->sum('grand_total'),
            'discount_total' => $sales->sum('discount_total'),
            'vat_total' => $sales->sum('vat_total'),
        ];
        
        // Top products
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($branchId, $date) {
            $q->where('branch_id', $branchId)->whereDate('sold_at', $date)->where('status', 'completed');
        })
        ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_amount'))
        ->groupBy('product_name')
        ->orderByDesc('total_amount')
        ->limit(10)
        ->get();
        
        // Hourly distribution
        $driver = DB::getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "strftime('%H', sold_at) as hour"
            : "LPAD(HOUR(sold_at), 2, '0') as hour";
        
        $hourly = Sale::where('branch_id', $branchId)
            ->whereDate('sold_at', $date)
            ->where('status', 'completed')
            ->selectRaw("{$hourExpr}, COUNT(*) as count, SUM(grand_total) as total")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        return response()->json([
            'summary' => $summary,
            'top_products' => $topProducts,
            'hourly' => $hourly,
        ]);
    }

    public function topProducts(Request $request)
    {
        $days = $request->get('days', 7);
        $branchId = session('branch_id');
        
        $products = SaleItem::whereHas('sale', function ($q) use ($branchId, $days) {
            $q->where('branch_id', $branchId)
              ->where('sold_at', '>=', Carbon::now()->subDays($days))
              ->where('status', 'completed');
        })
        ->select('product_name', 'product_id', 
            DB::raw('SUM(quantity) as total_qty'), 
            DB::raw('SUM(total) as total_amount'),
            DB::raw('COUNT(DISTINCT sale_id) as sale_count'))
        ->groupBy('product_name', 'product_id')
        ->orderByDesc('total_amount')
        ->limit(20)
        ->get();
        
        return response()->json($products);
    }
}
