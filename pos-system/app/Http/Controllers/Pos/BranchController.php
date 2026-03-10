<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Staff;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\HardwareDevice;
use App\Models\Order;
use Illuminate\Http\Request;

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

        $devices = HardwareDevice::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branch->id)
            ->orderBy('type')
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'success' => true,
            'printers' => $devices->where('type', 'printer')->values(),
            'cash_drawers' => $devices->where('type', 'cash_drawer')->values(),
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
