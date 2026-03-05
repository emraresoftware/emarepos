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
}
