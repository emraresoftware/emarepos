<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $query = Staff::where('branch_id', $branchId)->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('role', 'like', "%{$s}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === '1');
        }

        $staff = $query->paginate(30)->withQueryString();

        // 4 sorgu yerine 2 sorgu ile stats hesapla
        $agg = Staff::where('branch_id', $branchId)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active, COALESCE(SUM(total_sales), 0) as total_sales_sum")
            ->first();
        $topSeller = Staff::where('branch_id', $branchId)->orderBy('total_sales', 'desc')->value('name');
        $stats = [
            'total'        => (int) ($agg->total ?? 0),
            'active'       => (int) ($agg->active ?? 0),
            'total_sales'  => (float) ($agg->total_sales_sum ?? 0),
            'top_seller'   => $topSeller ?? '—',
        ];

        return view('pos.staff.index', compact('staff', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'pin'   => 'nullable|string|max:10',
        ]);
        $data['tenant_id'] = session('tenant_id');
        $data['branch_id'] = session('branch_id');
        $data['is_active'] = true;
        $data['permissions'] = $request->input('permissions', []);

        $member = Staff::create($data);
        return response()->json(['success' => true, 'staff' => $member]);
    }

    public function update(Request $request, Staff $staff)
    {
        if ($staff->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'role'      => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
            'pin'       => 'nullable|string|max:10',
        ]);
        $data['permissions'] = $request->input('permissions', []);
        $staff->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy(Staff $staff)
    {
        if ($staff->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $staff->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Personel Detaylı Performans
     */
    public function performance(Request $request, Staff $staff)
    {
        if ($staff->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $branchId = session('branch_id');
        $days = (int) $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $driver = \DB::getDriverName();

        // Temel satış istatistikleri
        $salesQuery = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('sold_at', '>=', $startDate);

        $totalSales = (clone $salesQuery)->count();
        $totalRevenue = (clone $salesQuery)->sum('grand_total');
        $totalDiscount = (clone $salesQuery)->sum('discount_total');
        $avgBasket = $totalSales > 0 ? round($totalRevenue / $totalSales, 2) : 0;
        $totalItems = (clone $salesQuery)->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.quantity');

        // İade ve İptal sayıları
        $refundCount = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'refunded')
            ->where('sold_at', '>=', $startDate)
            ->count();

        $cancelCount = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'cancelled')
            ->where('sold_at', '>=', $startDate)
            ->count();

        // Günlük satış grafiği
        if ($driver === 'sqlite') {
            $dateExpr = "date(sold_at)";
        } else {
            $dateExpr = "DATE(sold_at)";
        }

        $dailySales = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('sold_at', '>=', $startDate)
            ->selectRaw("{$dateExpr} as date, COUNT(*) as count, SUM(grand_total) as total")
            ->groupByRaw($dateExpr)
            ->orderBy('date')
            ->get();

        // Saatlik dağılım
        if ($driver === 'sqlite') {
            $hourExpr = "cast(strftime('%H', sold_at) as integer)";
        } else {
            $hourExpr = "HOUR(sold_at)";
        }

        $hourlyDistribution = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('sold_at', '>=', $startDate)
            ->selectRaw("{$hourExpr} as hour, COUNT(*) as count, SUM(grand_total) as total")
            ->groupByRaw($hourExpr)
            ->orderBy('hour')
            ->get();

        // En çok sattığı ürünler
        $topProducts = \DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.staff_name', $staff->name)
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->where('sales.sold_at', '>=', $startDate)
            ->select('sale_items.product_name')
            ->selectRaw('SUM(sale_items.quantity) as qty, SUM(sale_items.total) as total')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Ödeme tipi dağılımı
        $cashTotal = (clone $salesQuery)->sum('cash_amount');
        $cardTotal = (clone $salesQuery)->sum('card_amount');

        return response()->json([
            'staff' => $staff,
            'stats' => [
                'total_sales' => $totalSales,
                'total_revenue' => round($totalRevenue, 2),
                'total_discount' => round($totalDiscount, 2),
                'avg_basket' => $avgBasket,
                'total_items' => $totalItems,
                'refund_count' => $refundCount,
                'cancel_count' => $cancelCount,
                'cash_total' => round($cashTotal, 2),
                'card_total' => round($cardTotal, 2),
            ],
            'daily_sales' => $dailySales,
            'hourly_distribution' => $hourlyDistribution,
            'top_products' => $topProducts,
        ]);
    }
}
