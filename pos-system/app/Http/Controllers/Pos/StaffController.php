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
        $query = Staff::orderBy('name');

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

        $stats = [
            'total'        => Staff::count(),
            'active'       => Staff::where('is_active', true)->count(),
            'total_sales'  => Staff::sum('total_sales'),
            'top_seller'   => Staff::orderBy('total_sales', 'desc')->first()?->name ?? '—',
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
        $staff->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Personel Detaylı Performans
     */
    public function performance(Request $request, Staff $staff)
    {
        $branchId = session('branch_id');
        $days = (int) $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $driver = \DB::getDriverName();

        // Temel satış istatistikleri
        $salesQuery = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate);

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
            ->where('created_at', '>=', $startDate)
            ->count();

        $cancelCount = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'cancelled')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Günlük satış grafiği
        if ($driver === 'sqlite') {
            $dateExpr = "date(created_at)";
        } else {
            $dateExpr = "DATE(created_at)";
        }

        $dailySales = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("{$dateExpr} as date, COUNT(*) as count, SUM(grand_total) as total")
            ->groupByRaw($dateExpr)
            ->orderBy('date')
            ->get();

        // Saatlik dağılım
        if ($driver === 'sqlite') {
            $hourExpr = "cast(strftime('%H', created_at) as integer)";
        } else {
            $hourExpr = "HOUR(created_at)";
        }

        $hourlyDistribution = Sale::where('staff_name', $staff->name)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
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
            ->where('sales.created_at', '>=', $startDate)
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
