<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Staff;
use App\Models\Expense;
use App\Models\Income;
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

                $satilanUrunler = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
                        $q->where('branch_id', $branchId)
                            ->where('status', 'completed')
                            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                })
                ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                        'sale_items.product_name',
                        DB::raw("COALESCE(categories.name, 'Kategorisiz') as category_name"),
                        DB::raw('SUM(sale_items.quantity) as total_qty'),
                        DB::raw('SUM(sale_items.total) as total_amount'),
                        DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
                        DB::raw('CASE WHEN SUM(sale_items.quantity) > 0 THEN SUM(sale_items.total) / SUM(sale_items.quantity) ELSE 0 END as avg_unit_price')
                )
                ->groupBy('sale_items.product_name', DB::raw("COALESCE(categories.name, 'Kategorisiz')"))
                ->orderByDesc('total_amount')
                ->limit(200)
                ->get();

        // Category breakdown
        $categoryStats = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
        ->join('products', 'sale_items.product_id', '=', 'products.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    DB::raw("COALESCE(categories.name, 'Kategorisiz') as category_name"),
                    DB::raw('SUM(sale_items.total) as revenue'),
                    DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
                    DB::raw('SUM(sale_items.quantity) as total_qty'),
                    DB::raw('COUNT(DISTINCT sale_items.product_id) as product_count')
                )
                ->groupBy(DB::raw("COALESCE(categories.name, 'Kategorisiz')"))
        ->orderByDesc('revenue')
        ->get();

        return view('pos.reports.index', compact(
            'stats',
            'dailySales',
            'paymentStats',
            'topProducts',
            'satilanUrunler',
            'categoryStats',
            'startDate',
            'endDate'
        ));
    }

    public function daily(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $branchId = session('branch_id');
        
        // 7 ayrı sorgu yerine tek selectRaw (N+1 önlemi)
        $salesAgg = Sale::where('branch_id', $branchId)
            ->whereDate('sold_at', $date)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(grand_total),0) as grand_total, COALESCE(SUM(cash_amount),0) as cash_total, COALESCE(SUM(card_amount),0) as card_total, COALESCE(SUM(credit_amount),0) as credit_total, COALESCE(SUM(discount_total),0) as discount_total, COALESCE(SUM(vat_total),0) as vat_total')
            ->first();
        $summary = [
            'date'          => $date,
            'total_sales'   => (int)   ($salesAgg->total_sales ?? 0),
            'grand_total'   => (float) ($salesAgg->grand_total ?? 0),
            'cash_total'    => (float) ($salesAgg->cash_total ?? 0),
            'card_total'    => (float) ($salesAgg->card_total ?? 0),
            'credit_total'  => (float) ($salesAgg->credit_total ?? 0),
            'discount_total'=> (float) ($salesAgg->discount_total ?? 0),
            'vat_total'     => (float) ($salesAgg->vat_total ?? 0),
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

    /**
     * Kâr / Zarar Analizi
     */
    public function profitLoss(Request $request)
    {
        $branchId = session('branch_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        // Satış gelirleri
        $salesRevenue = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('grand_total');

        // Satılan ürünlerin maliyet toplamı
        $costOfGoods = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
                ->selectRaw('SUM(sale_items.quantity * sale_items.purchase_cost) as cost')
        ->value('cost') ?? 0;

        // İndirimler
        $discountTotal = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('discount_total');

        // İadeler
        $refundTotal = Sale::where('branch_id', $branchId)
            ->where('status', 'refunded')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('grand_total');

        // Giderler
        $expenses = Expense::where('branch_id', $branchId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Gelirler (satış dışı)
        $otherIncome = Income::where('branch_id', $branchId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $grossProfit = $salesRevenue - $costOfGoods;
        $netProfit = $grossProfit - $discountTotal - $refundTotal - $expenses + $otherIncome;
        $profitMargin = $salesRevenue > 0 ? round(($grossProfit / $salesRevenue) * 100, 2) : 0;

        // Günlük kâr grafiği
        $dailyProfit = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('DATE(sold_at) as date, SUM(grand_total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return ['date' => $row->date, 'revenue' => $row->revenue];
            });

        // En karlı ürünler
        $profitableProducts = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
        ->select(
            'sale_items.product_name',
            DB::raw('SUM(sale_items.quantity) as qty'),
            DB::raw('SUM(sale_items.total) as revenue'),
            DB::raw('SUM(sale_items.quantity * sale_items.purchase_cost) as cost'),
            DB::raw('SUM(sale_items.total) - SUM(sale_items.quantity * sale_items.purchase_cost) as profit')
        )
        ->groupBy('sale_items.product_name')
        ->orderByDesc('profit')
        ->limit(15)
        ->get();

        return response()->json([
            'sales_revenue' => $salesRevenue,
            'cost_of_goods' => $costOfGoods,
            'gross_profit' => $grossProfit,
            'discount_total' => $discountTotal,
            'refund_total' => $refundTotal,
            'expenses' => $expenses,
            'other_income' => $otherIncome,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
            'daily_profit' => $dailyProfit,
            'profitable_products' => $profitableProducts,
        ]);
    }

    /**
     * Personel Performans Raporu
     */
    public function staffReport(Request $request)
    {
        $branchId = session('branch_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $staffStats = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('staff_name')
            ->select(
                'staff_name',
                DB::raw('COUNT(*) as sale_count'),
                DB::raw('SUM(grand_total) as total_revenue'),
                DB::raw('SUM(total_items) as total_items'),
                DB::raw('AVG(grand_total) as avg_basket'),
                DB::raw('SUM(discount_total) as total_discount'),
                DB::raw('SUM(cash_amount) as cash_total'),
                DB::raw('SUM(card_amount) as card_total')
            )
            ->groupBy('staff_name')
            ->orderByDesc('total_revenue')
            ->get();

        // İade oranları
        $staffRefunds = Sale::where('branch_id', $branchId)
            ->where('status', 'refunded')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('staff_name')
            ->select('staff_name', DB::raw('COUNT(*) as refund_count'), DB::raw('SUM(grand_total) as refund_total'))
            ->groupBy('staff_name')
            ->pluck('refund_count', 'staff_name')
            ->toArray();

        // Saatlik dağılım
        $driver = DB::getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "strftime('%H', sold_at) as hour"
            : "LPAD(HOUR(sold_at), 2, '0') as hour";

        $hourlyByStaff = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('staff_name')
            ->selectRaw("{$hourExpr}, staff_name, COUNT(*) as count")
            ->groupBy('hour', 'staff_name')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'staff_stats' => $staffStats,
            'staff_refunds' => $staffRefunds,
            'hourly_by_staff' => $hourlyByStaff,
        ]);
    }

    /**
     * Kategori Bazlı Detaylı Rapor
     */
    public function categoryReport(Request $request)
    {
        $branchId = session('branch_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $categoryStats = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
        ->join('products', 'sale_items.product_id', '=', 'products.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->select(
            DB::raw('COALESCE(categories.name, \'Kategorisiz\') as category_name'),
            DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.total) as revenue'),
            DB::raw('SUM(sale_items.total) - SUM(sale_items.quantity * sale_items.purchase_cost) as profit'),
            DB::raw('COUNT(DISTINCT sale_items.product_id) as product_count')
        )
        ->groupBy('category_name')
        ->orderByDesc('revenue')
        ->get();

        $totalRevenue = $categoryStats->sum('revenue');
        $categoryStats->each(function ($c) use ($totalRevenue) {
            $c->percentage = $totalRevenue > 0 ? round(($c->revenue / $totalRevenue) * 100, 1) : 0;
        });

        return response()->json([
            'categories' => $categoryStats,
            'total_revenue' => $totalRevenue,
        ]);
    }

    /**
     * Urun Bazli Detayli Rapor
     */
    public function productReport(Request $request)
    {
        $branchId = session('branch_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $products = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
              ->where('status', 'completed')
              ->whereBetween('sold_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        })
        ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->select(
            'sale_items.product_name',
            DB::raw("COALESCE(categories.name, 'Kategorisiz') as category_name"),
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.total) as total_amount'),
            DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
            DB::raw('CASE WHEN SUM(sale_items.quantity) > 0 THEN SUM(sale_items.total) / SUM(sale_items.quantity) ELSE 0 END as avg_unit_price')
        )
        ->groupBy('sale_items.product_name', DB::raw("COALESCE(categories.name, 'Kategorisiz')"))
        ->orderByDesc('total_amount')
        ->limit(500)
        ->get();

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Dönem Karşılaştırma
     */
    public function periodComparison(Request $request)
    {
        $branchId = session('branch_id');
        $period1Start = $request->get('p1_start', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $period1End = $request->get('p1_end', Carbon::today()->format('Y-m-d'));
        $period2Start = $request->get('p2_start', Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $period2End = $request->get('p2_end', Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'));

        $getStats = function ($start, $end) use ($branchId) {
            $result = Sale::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereBetween('sold_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as revenue, COALESCE(SUM(discount_total),0) as discount, COALESCE(SUM(total_items),0) as items')
                ->first();
            return [
                'revenue' => (float) $result->revenue,
                'count' => (int) $result->cnt,
                'avg_basket' => $result->cnt > 0 ? round($result->revenue / $result->cnt, 2) : 0,
                'discount' => (float) $result->discount,
                'items' => (int) $result->items,
            ];
        };

        $p1 = $getStats($period1Start, $period1End);
        $p2 = $getStats($period2Start, $period2End);

        $changes = [];
        foreach ($p1 as $key => $val) {
            $old = $p2[$key] ?? 0;
            $changes[$key] = $old > 0 ? round((($val - $old) / $old) * 100, 1) : ($val > 0 ? 100 : 0);
        }

        return response()->json([
            'period1' => array_merge($p1, ['start' => $period1Start, 'end' => $period1End]),
            'period2' => array_merge($p2, ['start' => $period2Start, 'end' => $period2End]),
            'changes' => $changes,
        ]);
    }

    /**
     * Şüpheli İşlem Raporlama
     */
    public function suspiciousTransactions(Request $request)
    {
        $branchId = session('branch_id');
        $startDate = $request->input('start', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end', Carbon::now()->toDateString());

        $suspicious = collect();

        // 1) İade edilen satışlar
        $refunds = Sale::where('branch_id', $branchId)
            ->where('status', 'refunded')
            ->whereBetween('sold_at', [$startDate, $endDate . ' 23:59:59'])
            ->with('items')
            ->get()
            ->map(function ($sale) {
                return [
                    'type' => 'refund',
                    'type_label' => 'İade',
                    'severity' => 'high',
                    'sale_id' => $sale->id,
                    'amount' => $sale->grand_total,
                    'staff' => $sale->staff_name ?? 'Bilinmiyor',
                    'date' => $sale->sold_at->format('d.m.Y H:i'),
                    'detail' => ($sale->items->count() ?? 0) . ' kalem iade',
                ];
            });
        $suspicious = $suspicious->concat($refunds);

        // 2) İptal edilen satışlar
        $cancelled = Sale::where('branch_id', $branchId)
            ->where('status', 'cancelled')
            ->whereBetween('sold_at', [$startDate, $endDate . ' 23:59:59'])
            ->get()
            ->map(function ($sale) {
                return [
                    'type' => 'cancelled',
                    'type_label' => 'İptal',
                    'severity' => 'medium',
                    'sale_id' => $sale->id,
                    'amount' => $sale->grand_total,
                    'staff' => $sale->staff_name ?? 'Bilinmiyor',
                    'date' => $sale->sold_at->format('d.m.Y H:i'),
                    'detail' => 'Satış iptal edildi',
                ];
            });
        $suspicious = $suspicious->concat($cancelled);

        // 3) Yüksek iskonto oranı (%30 üzeri)
        $highDiscount = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereRaw('discount_total > 0 AND (discount_total / (grand_total + discount_total)) > 0.3')
            ->get()
            ->map(function ($sale) {
                $original = $sale->grand_total + $sale->discount_total;
                $rate = $original > 0 ? round(($sale->discount_total / $original) * 100, 1) : 0;
                return [
                    'type' => 'high_discount',
                    'type_label' => 'Yüksek İskonto',
                    'severity' => 'medium',
                    'sale_id' => $sale->id,
                    'amount' => $sale->discount_total,
                    'staff' => $sale->staff_name ?? 'Bilinmiyor',
                    'date' => $sale->sold_at->format('d.m.Y H:i'),
                    'detail' => '%' . $rate . ' iskonto (₺' . number_format($sale->discount_total, 2) . ')',
                ];
            });
        $suspicious = $suspicious->concat($highDiscount);

        // 4) Maliyetin altına satış
        $belowCost = \DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereRaw('sale_items.purchase_cost > 0 AND sale_items.unit_price < sale_items.purchase_cost')
            ->select('sales.id as sale_id', 'sales.staff_name', 'sales.sold_at', 'sale_items.product_name', 'sale_items.unit_price', 'sale_items.purchase_cost')
            ->get()
            ->map(function ($row) {
                return [
                    'type' => 'below_cost',
                    'type_label' => 'Maliyetin Altı',
                    'severity' => 'high',
                    'sale_id' => $row->sale_id,
                    'amount' => $row->purchase_cost - $row->unit_price,
                    'staff' => $row->staff_name ?? 'Bilinmiyor',
                    'date' => Carbon::parse($row->sold_at)->format('d.m.Y H:i'),
                    'detail' => $row->product_name . ': Satış ₺' . number_format($row->unit_price, 2) . ' < Alış ₺' . number_format($row->purchase_cost, 2),
                ];
            });
        $suspicious = $suspicious->concat($belowCost);

        // 5) Gece geç saatte satış (22:00 - 06:00)
        $driver = \DB::getDriverName();
        if ($driver === 'sqlite') {
            $hourExpr = "cast(strftime('%H', sold_at) as integer)";
        } else {
            $hourExpr = "HOUR(sold_at)";
        }

        $lateNight = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereRaw("({$hourExpr} >= 22 OR {$hourExpr} < 6)")
            ->get()
            ->map(function ($sale) {
                return [
                    'type' => 'late_night',
                    'type_label' => 'Gece İşlemi',
                    'severity' => 'low',
                    'sale_id' => $sale->id,
                    'amount' => $sale->grand_total,
                    'staff' => $sale->staff_name ?? 'Bilinmiyor',
                    'date' => $sale->sold_at->format('d.m.Y H:i'),
                    'detail' => 'Geç saatte yapılan satış',
                ];
            });
        $suspicious = $suspicious->concat($lateNight);

        // Severity'ye göre sırala (high > medium > low)
        $severityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        $suspicious = $suspicious->sortBy(fn($s) => $severityOrder[$s['severity']] ?? 3)->values();

        // Özet istatistikler
        $summary = [
            'total' => $suspicious->count(),
            'high' => $suspicious->where('severity', 'high')->count(),
            'medium' => $suspicious->where('severity', 'medium')->count(),
            'low' => $suspicious->where('severity', 'low')->count(),
            'refund_count' => $suspicious->where('type', 'refund')->count(),
            'cancelled_count' => $suspicious->where('type', 'cancelled')->count(),
            'below_cost_count' => $suspicious->where('type', 'below_cost')->count(),
            'total_loss' => $suspicious->sum('amount'),
        ];

        return response()->json(['suspicious' => $suspicious->toArray(), 'summary' => $summary]);
    }
}
