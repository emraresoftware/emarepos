<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\HardwareDevice;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::withCount(['users', 'restaurantTables', 'cashRegisters'])
            ->orderBy('name')
            ->get();

        return view('pos.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'is_center' => 'nullable|boolean',
            'price_edit_locked' => 'nullable|boolean',
        ]);

        $data['tenant_id'] = session('tenant_id');
        $data['is_active'] = true;
        $data['settings'] = [
            'is_center' => $request->boolean('is_center'),
            'price_edit_locked' => $request->boolean('price_edit_locked'),
        ];
        $branch = Branch::create($data);

        return response()->json(['success' => true, 'branch' => $branch]);
    }

    public function update(Request $request, Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'is_center' => 'nullable|boolean',
            'price_edit_locked' => 'nullable|boolean',
        ]);

        $settings = $branch->settings ?? [];
        $settings['is_center'] = $request->boolean('is_center');
        $settings['price_edit_locked'] = $request->boolean('price_edit_locked');

        $branch->update(array_merge($data, ['settings' => $settings]));
        return response()->json(['success' => true, 'branch' => $branch->fresh()]);
    }

    public function destroy(Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        // Aktif şubeyi silmeye çalışıyorsa
        if ((int) session('branch_id') === $branch->id) {
            return response()->json(['success' => false, 'message' => 'Aktif şubenizi silemezsiniz.'], 422);
        }

        // Bağlı satış var mı?
        $saleCount = Sale::where('branch_id', $branch->id)->count();
        if ($saleCount > 0) {
            // Soft-delete: pasife çek
            $branch->update(['is_active' => false]);
            return response()->json(['success' => true, 'message' => "Şube pasife alındı ({$saleCount} satış kaydı var, kalıcı silme yapılmadı)."]);
        }

        // Bağlı personel var mı?
        $staffCount = Staff::where('branch_id', $branch->id)->count();
        if ($staffCount > 0) {
            $branch->update(['is_active' => false]);
            return response()->json(['success' => true, 'message' => "Şube pasife alındı ({$staffCount} personel kaydı var)."]);
        }

        $branch->delete();
        return response()->json(['success' => true, 'message' => 'Şube silindi.']);
    }

    public function modules(Branch $branch)
    {
        if (!auth()->user()->is_super_admin && !auth()->user()->hasPermission('modules.manage')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $tenant = Tenant::find(session('tenant_id'));
        $tenantModules = $tenant?->modules()->get() ?? collect();
        $branchModules = $branch->modules()->get()->keyBy('id');

        $modules = $tenantModules
            ->filter(fn ($m) => in_array($m->scope, ['branch', 'both'], true))
            ->sortBy('sort_order')
            ->values()
            ->map(function ($module) use ($branchModules) {
                $pivot = $branchModules->get($module->id)?->pivot;
                return [
                    'id' => $module->id,
                    'code' => $module->code,
                    'name' => $module->name,
                    'description' => $module->description,
                    'scope' => $module->scope,
                    'is_core' => (bool) $module->is_core,
                    'tenant_active' => (bool) ($module->pivot?->is_active ?? false),
                    'branch_active' => (bool) ($pivot?->is_active ?? false),
                ];
            });

        return response()->json(['success' => true, 'modules' => $modules]);
    }

    public function updateModules(Request $request, Branch $branch)
    {
        if (!auth()->user()->is_super_admin && !auth()->user()->hasPermission('modules.manage')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'modules' => 'required|array',
            'modules.*.module_id' => 'required|integer|exists:modules,id',
            'modules.*.is_active' => 'nullable|boolean',
        ]);

        $tenant = Tenant::find(session('tenant_id'));
        $tenantModuleIds = $tenant?->modules()->wherePivot('is_active', true)->pluck('modules.id')->all() ?? [];

        $allowedModules = Module::whereIn('id', $tenantModuleIds)
            ->whereIn('scope', ['branch', 'both'])
            ->pluck('id')
            ->all();

        $syncData = [];
        foreach ($data['modules'] as $item) {
            $moduleId = (int) $item['module_id'];
            if (!in_array($moduleId, $allowedModules, true)) {
                continue;
            }
            $syncData[$moduleId] = [
                'is_active' => (bool) ($item['is_active'] ?? false),
                'activated_at' => ($item['is_active'] ?? false) ? now() : null,
            ];
        }

        $branch->modules()->sync($syncData);

        return response()->json(['success' => true]);
    }

    public function devices(Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $devices = \App\Models\HardwareDevice::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->orderBy('type')
            ->orderByDesc('is_default')
            ->get();

        $users = \App\Models\User::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
            
        $terminals = \App\Models\PosTerminal::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->with('responsibleUser:id,name,email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'printers' => $devices->where('type', 'printer')->values(),
            'cash_drawers' => $devices->where('type', 'cash_drawer')->values(),
            'users' => $users,
            'terminals' => $terminals,
            'settings' => [
                'receipt_printer_id' => $branch->settings['receipt_printer_id'] ?? null,
                'kitchen_printer_id' => $branch->settings['kitchen_printer_id'] ?? null,
                'cash_drawer_device_id' => $branch->settings['cash_drawer_device_id'] ?? null,
            ],
        ]);
    }

    public function updateDeviceSettings(Request $request, Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'receipt_printer_id' => 'nullable|integer',
            'kitchen_printer_id' => 'nullable|integer',
            'cash_drawer_device_id' => 'nullable|integer',
        ]);

        $allowedPrinterIds = HardwareDevice::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->where('type', 'printer')
            ->pluck('id')
            ->all();

        $allowedCashDrawerIds = HardwareDevice::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->where('type', 'cash_drawer')
            ->pluck('id')
            ->all();

        $receiptId = isset($data['receipt_printer_id']) ? (int) $data['receipt_printer_id'] : null;
        $kitchenId = isset($data['kitchen_printer_id']) ? (int) $data['kitchen_printer_id'] : null;
        $cashDrawerId = isset($data['cash_drawer_device_id']) ? (int) $data['cash_drawer_device_id'] : null;

        $settings = $branch->settings ?? [];
        $settings['receipt_printer_id'] = in_array($receiptId, $allowedPrinterIds, true) ? $receiptId : null;
        $settings['kitchen_printer_id'] = in_array($kitchenId, $allowedPrinterIds, true) ? $kitchenId : null;
        $settings['cash_drawer_device_id'] = in_array($cashDrawerId, $allowedCashDrawerIds, true) ? $cashDrawerId : null;

        $branch->update(['settings' => $settings]);

        return response()->json(['success' => true]);
    }

    public function saveTerminal(Request $request, Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'responsible_user_id' => 'nullable|integer',
            'receipt_printer_id' => 'nullable|integer',
            'kitchen_printer_id' => 'nullable|integer',
            'cash_drawer_id' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        $allowedUserIds = \App\Models\User::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->pluck('id')
            ->all();

        $responsibleUserId = isset($data['responsible_user_id']) ? (int) $data['responsible_user_id'] : null;

        $terminal = null;
        if (!empty($data['id'])) {
            $terminal = \App\Models\PosTerminal::where('tenant_id', session('tenant_id'))
                ->where('branch_id', $branch->id)
                ->findOrFail($data['id']);
        } else {
            $terminal = new \App\Models\PosTerminal();
            $terminal->tenant_id = session('tenant_id');
            $terminal->branch_id = $branch->id;
        }

        $terminal->fill([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'responsible_user_id' => in_array($responsibleUserId, $allowedUserIds, true) ? $responsibleUserId : null,
            'receipt_printer_id' => $data['receipt_printer_id'],
            'kitchen_printer_id' => $data['kitchen_printer_id'],
            'cash_drawer_id' => $data['cash_drawer_id'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
        $terminal->save();

        return response()->json(['success' => true, 'terminal' => $terminal->load('responsibleUser:id,name,email')]);
    }

    public function deleteTerminal(Branch $branch, \App\Models\PosTerminal $terminal)
    {
        if ($branch->tenant_id !== (int) session('tenant_id') || $terminal->tenant_id !== (int) session('tenant_id') || $terminal->branch_id !== $branch->id) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $terminal->delete();
        return response()->json(['success' => true]);
    }

    public function report(Request $request, Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->subDays(29)->startOfDay();
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        $salesQ = Sale::where('branch_id', $branch->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        // KPI
        $totalRevenue  = (float) (clone $salesQ)->sum('grand_total');
        $totalCount    = (int)   (clone $salesQ)->count();
        $totalDiscount = (float) (clone $salesQ)->sum('discount_total');
        $cashTotal     = (float) (clone $salesQ)->sum('cash_amount');
        $cardTotal     = (float) (clone $salesQ)->sum('card_amount');
        $creditTotal   = (float) (clone $salesQ)->sum('credit_amount');
        $transferTotal = (float) (clone $salesQ)->sum('transfer_amount');
        $avgTicket     = $totalCount > 0 ? round($totalRevenue / $totalCount, 2) : 0;

        // Günlük kırılım (chart)
        $daily = (clone $salesQ)
            ->selectRaw("STRFTIME('%Y-%m-%d', created_at) as day, SUM(grand_total) as revenue, COUNT(*) as cnt")
            ->groupByRaw("STRFTIME('%Y-%m-%d', created_at)")
            ->orderBy('day')
            ->get()
            ->map(fn($r) => ['day' => $r->day, 'revenue' => (float) $r->revenue, 'cnt' => (int) $r->cnt]);

        // Ödeme yöntemi dağılımı
        $payments = [
            ['method' => 'Nakit',   'total' => $cashTotal],
            ['method' => 'Kart',    'total' => $cardTotal],
            ['method' => 'Veresiye','total' => $creditTotal],
            ['method' => 'Havale',  'total' => $transferTotal],
        ];

        // En çok satan 10 ürün (sale_items üzerinden)
        $saleIds = (clone $salesQ)->pluck('id');
        $topProducts = SaleItem::whereIn('sale_id', $saleIds)
            ->selectRaw('product_id, MAX(product_name) as product_name, SUM(quantity) as total_qty, SUM(total) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'product_id'    => $r->product_id,
                'name'          => $r->product_name,
                'total_qty'     => (float) $r->total_qty,
                'total_revenue' => (float) $r->total_revenue,
            ]);

        // En çok alışveriş yapan 10 müşteri
        $topCustomers = (clone $salesQ)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as sale_count, SUM(grand_total) as total_revenue')
            ->groupBy('customer_id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->with('customer:id,name,phone')
            ->get()
            ->map(fn($r) => [
                'customer_id'   => $r->customer_id,
                'name'          => $r->customer?->name ?? '—',
                'phone'         => $r->customer?->phone ?? '',
                'sale_count'    => (int) $r->sale_count,
                'total_revenue' => (float) $r->total_revenue,
            ]);

        // Şubeye atanmış ürünler
        $products = $branch->products()
            ->with('category:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'barcode'    => $p->barcode,
                'category'   => $p->category?->name,
                'stock'      => (float) $p->pivot->stock_quantity,
                'sale_price' => (float) $p->pivot->sale_price,
            ]);

        // Personel
        $staff = $branch->staff()
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'phone']);

        return response()->json([
            'success'       => true,
            'period'        => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'kpi'           => compact('totalRevenue', 'totalCount', 'totalDiscount', 'cashTotal', 'cardTotal', 'creditTotal', 'transferTotal', 'avgTicket'),
            'daily'         => $daily,
            'payments'      => $payments,
            'top_products'  => $topProducts,
            'top_customers' => $topCustomers,
            'products'      => $products,
            'staff'         => $staff,
        ]);
    }

    public function stats(Branch $branch)
    {
        if ($branch->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $todayStart = now()->startOfDay();
        $last7 = now()->subDays(7);
        $last30 = now()->subDays(30);

        $salesBase = Sale::where('branch_id', $branch->id)->where('status', 'completed');

        $totalRevenue = (float) (clone $salesBase)->sum('grand_total');
        $totalCount = (int) (clone $salesBase)->count();
        $todayRevenue = (float) (clone $salesBase)->where('created_at', '>=', $todayStart)->sum('grand_total');
        $todayCount = (int) (clone $salesBase)->where('created_at', '>=', $todayStart)->count();
        $last7Revenue = (float) (clone $salesBase)->where('created_at', '>=', $last7)->sum('grand_total');
        $last30Revenue = (float) (clone $salesBase)->where('created_at', '>=', $last30)->sum('grand_total');

        $avgTicket = $totalCount > 0 ? round($totalRevenue / $totalCount, 2) : 0;

        return response()->json([
            'success' => true,
            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_sales' => $totalCount,
                'today_revenue' => $todayRevenue,
                'today_sales' => $todayCount,
                'last7_revenue' => $last7Revenue,
                'last30_revenue' => $last30Revenue,
                'avg_ticket' => $avgTicket,
                'users' => $branch->users()->count(),
                'tables' => $branch->restaurantTables()->count(),
                'cash_registers' => $branch->cashRegisters()->count(),
                'orders' => Order::where('branch_id', $branch->id)->count(),
            ],
        ]);
    }
}
