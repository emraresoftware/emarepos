<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Models\TableRegion;
use App\Models\TableSession;
use App\Models\Order;
use App\Services\TableService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    protected TableService $tableService;
    protected SaleService $saleService;

    public function __construct(TableService $tableService, SaleService $saleService)
    {
        $this->tableService = $tableService;
        $this->saleService = $saleService;
    }

    /**
     * Masa haritası
     */
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $regions = TableRegion::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $tables = RestaurantTable::where('branch_id', $branchId)
            ->where('is_active', true)
            ->with(['activeSession.orders.items'])
            ->orderBy('sort_order')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['regions' => $regions, 'tables' => $tables]);
        }

        return view('pos.tables.index', compact('regions', 'tables'));
    }

    /**
     * Masa aç
     */
    public function open(Request $request, RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }

        $request->validate([
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', session('tenant_id'))],
            'customer_count' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $session = $this->tableService->openTable(
                $table->id,
                auth()->id(),
                $request->customer_id,
                $request->customer_count ?? 1
            );
            return response()->json(['success' => true, 'session' => $session]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Masa detay
     */
    public function detail(RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            abort(403, 'Bu masaya erişim yetkiniz yok.');
        }
        $session = $table->activeSession;
        if ($session) {
            $session->load('orders.items');
        }
        
        $categories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get();
        
        $emptyTables = RestaurantTable::where('branch_id', session('branch_id'))
            ->where('is_active', true)
            ->where('status', 'empty')
            ->where('id', '!=', $table->id)
            ->orderBy('sort_order')
            ->get();

        $splitItemTotals = [];
        if ($session) {
            $splitItemTotals = $session->orders->flatMap->items
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'paid')
                ->pluck('total', 'id')
                ->toArray();
        }

        return view('pos.tables.detail', compact('table', 'session', 'categories', 'emptyTables', 'splitItemTotals'));
    }

    /**
     * Masaya sipariş ekle
     */
    public function addOrder(Request $request, RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $session = $table->activeSession;
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Masa açık değil.'], 422);
        }

        try {
            $order = $this->tableService->addOrder($session->id, [
                'branch_id' => session('branch_id'),
                'user_id' => auth()->id(),
                'items' => $request->items,
                'notes' => $request->notes,
                'kitchen_notes' => $request->kitchen_notes,
            ]);
            return response()->json(['success' => true, 'order' => $order]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Masa hesabı ödeme — closes table, creates sale
     */
    public function pay(Request $request, RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }

        $request->validate([
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', session('tenant_id'))],
            'payment_method' => ['nullable', 'string', 'regex:/^(cash|card|credit|mixed|transfer|other_.+)$/'],
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $session = $table->activeSession;
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Masa açık değil.'], 422);
        }

        try {
            $sale = DB::transaction(function () use ($request, $session, $table) {
                $summary = $this->tableService->getTableSummary($session->id);

                $saleItems = [];
                foreach ($summary['orders'] as $order) {
                    if ($order->status === 'cancelled') {
                        continue;
                    }

                    foreach ($order->items as $item) {
                        if (in_array($item->status, ['cancelled', 'paid'], true)) {
                            continue;
                        }

                        $saleItems[] = [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount,
                            'vat_rate' => $item->vat_rate,
                            'vat_amount' => $item->vat_amount,
                            'total' => $item->total,
                        ];
                    }
                }

                if (empty($saleItems)) {
                    throw new \Exception('Ödenecek kalem bulunamadı.');
                }

                $sale = $this->saleService->createSale([
                    'branch_id' => session('branch_id'),
                    'tenant_id' => session('tenant_id'),
                    'customer_id' => $request->customer_id ?? $session->customer_id,
                    'user_id' => auth()->id(),
                    'payment_method' => $request->payment_method ?? 'cash',
                    'items' => $saleItems,
                    'discount' => $request->discount ?? 0,
                    'cash_amount' => $request->cash_amount ?? 0,
                    'card_amount' => $request->card_amount ?? 0,
                    'credit_amount' => $request->credit_amount ?? 0,
                    'transfer_amount' => $request->transfer_amount ?? 0,
                    'staff_name' => auth()->user()->name,
                    'application' => 'pos',
                    'notes' => "Masa: {$table->name}",
                ]);

                foreach ($summary['orders'] as $order) {
                    $order->items()
                        ->whereNotIn('status', ['cancelled', 'paid'])
                        ->update(['status' => 'paid']);

                    $acikKalemVar = $order->items()
                        ->whereNotIn('status', ['cancelled', 'paid'])
                        ->exists();

                    $order->update([
                        'status' => $acikKalemVar ? $order->status : 'completed',
                        'sale_id' => $sale->id,
                    ]);
                }

                $this->tableService->closeTable($session->id, auth()->id());

                return $sale;
            });

            return response()->json([
                'success' => true,
                'message' => 'Ödeme alındı, masa kapatıldı.',
                'sale' => $sale,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Ürün bazlı (parçalı) ödeme — seçili order item ID'lerine göre kısmi satış
     */
    public function payPartial(Request $request, RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }

        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer',
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', session('tenant_id'))],
            'payment_method' => ['nullable', 'string', 'regex:/^(cash|card|credit|mixed|transfer|other_.+)$/'],
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
        ]);

        $session = $table->activeSession;
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Masa açık değil.'], 422);
        }

        $selectedItemIds = collect($request->item_ids ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (empty($selectedItemIds)) {
            return response()->json(['success' => false, 'message' => 'En az bir ürün seçiniz.'], 422);
        }

        try {
            [$sale, $tableClosed] = DB::transaction(function () use ($request, $session, $selectedItemIds, $table) {
                $session->load('orders.items');
                $saleItems = [];

                $odenecekKalemIdleri = [];

                foreach ($session->orders as $order) {
                    if ($order->status === 'cancelled') {
                        continue;
                    }

                    foreach ($order->items as $item) {
                        if (in_array($item->status, ['cancelled', 'paid'], true)) {
                            continue;
                        }

                        if (in_array($item->id, $selectedItemIds, true)) {
                            $odenecekKalemIdleri[] = $item->id;
                            $saleItems[] = [
                                'product_id'   => $item->product_id,
                                'product_name' => $item->product_name,
                                'quantity'     => $item->quantity,
                                'unit_price'   => $item->unit_price,
                                'discount'     => $item->discount,
                                'vat_rate'     => $item->vat_rate,
                                'vat_amount'   => $item->vat_amount,
                                'total'        => $item->total,
                            ];
                        }
                    }
                }

                if (empty($saleItems)) {
                    throw new \Exception('Seçili kalemler bulunamadı.');
                }

                $sale = $this->saleService->createSale([
                    'branch_id'      => session('branch_id'),
                    'tenant_id'      => session('tenant_id'),
                    'customer_id'    => $request->customer_id,
                    'user_id'        => auth()->id(),
                    'payment_method' => $request->payment_method ?? 'cash',
                    'items'          => $saleItems,
                    'discount'       => 0,
                    'cash_amount'    => $request->cash_amount ?? 0,
                    'card_amount'    => $request->card_amount ?? 0,
                    'credit_amount'  => $request->credit_amount ?? 0,
                    'transfer_amount' => $request->transfer_amount ?? 0,
                    'staff_name'     => auth()->user()->name,
                    'application'    => 'pos',
                    'notes'          => "Masa: {$table->name} (Kısmi Ödeme)",
                ]);

                \App\Models\OrderItem::whereIn('id', $odenecekKalemIdleri)
                    ->where('status', '!=', 'paid')
                    ->whereHas('order', fn($q) => $q->where('branch_id', session('branch_id')))
                    ->update(['status' => 'paid']);

                $session->load('orders.items');
                $hasUnpaid = false;
                foreach ($session->orders as $order) {
                    $acikKalemVar = $order->items->contains(fn ($item) => ! in_array($item->status, ['cancelled', 'paid'], true));
                    if ($acikKalemVar) {
                        $hasUnpaid = true;
                    } else {
                        $order->update(['status' => 'completed', 'sale_id' => $sale->id]);
                    }
                }

                if (! $hasUnpaid) {
                    $this->tableService->closeTable($session->id, auth()->id());
                }

                return [$sale, ! $hasUnpaid];
            });

            if ($tableClosed) {
                return response()->json(['success' => true, 'table_closed' => true, 'sale' => $sale, 'message' => 'Tüm kalemler ödendi, masa kapatıldı.']);
            }

            return response()->json(['success' => true, 'table_closed' => false, 'sale' => $sale, 'message' => 'Kısmi ödeme alındı.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Masa transfer — move session to another table
     */
    public function transfer(Request $request, RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }

        $request->validate([
            'target_table_id' => ['required', 'integer', Rule::exists('restaurant_tables', 'id')->where('branch_id', session('branch_id'))],
        ]);

        $targetTable = RestaurantTable::where('id', $request->target_table_id)
            ->where('branch_id', session('branch_id'))
            ->firstOrFail();
        
        if ($targetTable->status !== 'empty') {
            return response()->json(['success' => false, 'message' => 'Hedef masa müsait değil.'], 422);
        }

        $session = $table->activeSession;
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Kaynak masa açık değil.'], 422);
        }

        DB::transaction(function () use ($session, $table, $targetTable) {
            $session->update(['restaurant_table_id' => $targetTable->id]);
            $table->update(['status' => 'empty']);
            $targetTable->update(['status' => 'occupied']);
        });

        return response()->json(['success' => true, 'message' => 'Masa transferi başarılı.']);
    }

    // ─── Mekan (Region) CRUD ─────────────────────────────────

    /** Yeni mekan ekle */
    public function storeRegion(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'bg_color' => 'nullable|string|max:20',
            'icon'     => 'nullable|string|max:50',
        ]);

        $branchId = session('branch_id');
        $tenantId = session('tenant_id');
        $maxOrder = TableRegion::where('branch_id', $branchId)->max('sort_order') ?? 0;

        $region = TableRegion::create([
            'tenant_id'  => $tenantId,
            'branch_id'  => $branchId,
            'name'       => $request->name,
            'bg_color'   => $request->bg_color ?? '#f0f9ff',
            'icon'       => $request->icon ?? 'fa-location-dot',
            'description'=> $request->description,
            'sort_order' => $maxOrder + 1,
            'is_active'  => true,
        ]);

        return response()->json(['success' => true, 'region' => $region]);
    }

    /** Mekan güncelle */
    public function updateRegion(Request $request, TableRegion $region)
    {
        if ($region->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu mekana erişim yetkiniz yok.'], 403);
        }
        $request->validate([
            'name'     => 'required|string|max:100',
            'bg_color' => 'nullable|string|max:20',
            'icon'     => 'nullable|string|max:50',
        ]);

        $region->update($request->only(['name', 'bg_color', 'icon', 'description', 'sort_order']));

        return response()->json(['success' => true, 'region' => $region->fresh()]);
    }

    /** Mekan sil */
    public function destroyRegion(TableRegion $region)
    {
        if ($region->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu mekana erişim yetkiniz yok.'], 403);
        }
        // Mekandaki masaları bölgesiz bırak
        RestaurantTable::where('table_region_id', $region->id)
            ->update(['table_region_id' => null]);

        $region->delete();

        return response()->json(['success' => true, 'message' => 'Mekan silindi.']);
    }

    // ─── Masa CRUD ───────────────────────────────────────────

    /** Yeni masa ekle */
    public function storeTable(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'table_no'        => 'required|string|max:20',
            'capacity'        => 'required|integer|min:1|max:100',
            'shape'           => 'nullable|in:square,circle,rectangle',
            'table_region_id' => ['nullable', Rule::exists('table_regions', 'id')->where('branch_id', session('branch_id'))],
        ]);

        $branchId = session('branch_id');
        $tenantId = session('tenant_id');
        $maxOrder = RestaurantTable::where('branch_id', $branchId)->max('sort_order') ?? 0;

        // Yeni masanın konumunu bölge içinde otomatik yerleştir
        $posX = (($maxOrder % 8) * 12) + 2;
        $posY = (floor($maxOrder / 8) * 15) + 2;

        $table = RestaurantTable::create([
            'tenant_id'       => $tenantId,
            'branch_id'       => $branchId,
            'table_region_id' => $request->table_region_id,
            'table_no'        => $request->table_no,
            'name'            => $request->name,
            'capacity'        => $request->capacity,
            'shape'           => $request->shape ?? 'square',
            'color'           => $request->color,
            'pos_x'           => $posX,
            'pos_y'           => $posY,
            'sort_order'      => $maxOrder + 1,
            'status'          => 'empty',
            'is_active'       => true,
        ]);

        return response()->json(['success' => true, 'table' => $table]);
    }

    /** Masa güncelle (bilgi + konum) */
    public function updateTable(Request $request, RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }
        $request->validate([
            'name'            => 'sometimes|string|max:100',
            'table_no'        => 'sometimes|string|max:20',
            'capacity'        => 'sometimes|integer|min:1|max:100',
            'shape'           => 'nullable|in:square,circle,rectangle',
            'table_region_id' => ['nullable', Rule::exists('table_regions', 'id')->where('branch_id', session('branch_id'))],
            'pos_x'           => 'sometimes|numeric|min:0|max:95',
            'pos_y'           => 'sometimes|numeric|min:0|max:90',
        ]);

        $table->update($request->only([
            'name', 'table_no', 'capacity', 'shape', 'color',
            'table_region_id', 'pos_x', 'pos_y', 'is_active',
        ]));

        return response()->json(['success' => true, 'table' => $table->fresh()]);
    }

    /** Masa sil */
    public function destroyTable(RestaurantTable $table)
    {
        if ($table->branch_id !== (int) session('branch_id')) {
            return response()->json(['success' => false, 'message' => 'Bu masaya erişim yetkiniz yok.'], 403);
        }
        if ($table->status === 'occupied') {
            return response()->json(['success' => false, 'message' => 'Dolu masa silinemez.'], 422);
        }

        $table->delete();

        return response()->json(['success' => true, 'message' => 'Masa silindi.']);
    }

    /** Toplu konum kaydet (sürükle-bırak sonrası) */
    public function updateLayout(Request $request)
    {
        $request->validate([
            'positions'               => 'required|array',
            'positions.*.id'          => 'required|integer',
            'positions.*.pos_x'       => 'required|numeric|min:0|max:99',
            'positions.*.pos_y'       => 'required|numeric|min:0|max:99',
            'positions.*.region_id'   => ['nullable', Rule::exists('table_regions', 'id')->where('branch_id', session('branch_id'))],
        ]);

        foreach ($request->positions as $pos) {
            RestaurantTable::where('id', $pos['id'])
                ->where('branch_id', session('branch_id'))
                ->update([
                    'pos_x'           => $pos['pos_x'],
                    'pos_y'           => $pos['pos_y'],
                    'table_region_id' => $pos['region_id'] ?? null,
                ]);
        }

        return response()->json(['success' => true, 'message' => 'Layout kaydedildi.']);
    }
}
