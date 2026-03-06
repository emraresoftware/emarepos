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

        return view('pos.tables.detail', compact('table', 'session', 'categories', 'emptyTables'));
    }

    /**
     * Masaya sipariş ekle
     */
    public function addOrder(Request $request, RestaurantTable $table)
    {
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
        $session = $table->activeSession;
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Masa açık değil.'], 422);
        }

        try {
            $summary = $this->tableService->getTableSummary($session->id);
            
            // Collect all order items as sale items
            $saleItems = [];
            foreach ($summary['orders'] as $order) {
                if ($order->status === 'cancelled') continue;
                foreach ($order->items as $item) {
                    if ($item->status === 'cancelled') continue;
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
                'staff_name' => auth()->user()->name,
                'application' => 'pos',
                'notes' => "Masa: {$table->name}",
            ]);

            // Mark all orders as completed
            foreach ($summary['orders'] as $order) {
                $order->update(['status' => 'completed', 'sale_id' => $sale->id]);
            }

            // Close table
            $this->tableService->closeTable($session->id, auth()->id());

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
     * Masa transfer — move session to another table
     */
    public function transfer(Request $request, RestaurantTable $table)
    {
        $targetTable = RestaurantTable::findOrFail($request->target_table_id);
        
        if ($targetTable->status !== 'empty') {
            return response()->json(['success' => false, 'message' => 'Hedef masa müsait değil.'], 422);
        }

        $session = $table->activeSession;
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Kaynak masa açık değil.'], 422);
        }

        $session->update(['restaurant_table_id' => $targetTable->id]);
        $table->update(['status' => 'empty']);
        $targetTable->update(['status' => 'occupied']);

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
            'table_region_id' => 'nullable|exists:table_regions,id',
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
        $request->validate([
            'name'            => 'sometimes|string|max:100',
            'table_no'        => 'sometimes|string|max:20',
            'capacity'        => 'sometimes|integer|min:1|max:100',
            'shape'           => 'nullable|in:square,circle,rectangle',
            'table_region_id' => 'nullable|exists:table_regions,id',
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
            'positions.*.region_id'   => 'nullable|integer',
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
