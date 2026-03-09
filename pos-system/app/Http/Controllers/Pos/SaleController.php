<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\PaymentType;
use App\Models\Tenant;
use App\Models\ActivityLog;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * POS satış ekranı
     */
    public function index()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $paymentTypes = PaymentType::where('is_active', true)->orderBy('sort_order')->get();

        // Fiş ayarlarını tenant meta'dan al
        $tenant = Tenant::find(session('tenant_id'));
        $receiptSettings = [
            'receipt_header' => $tenant?->meta['receipt_header'] ?? '',
            'receipt_footer' => $tenant?->meta['receipt_footer'] ?? '',
            'auto_print_receipt' => $tenant?->meta['auto_print_receipt'] ?? false,
            'kitchen_print' => $tenant?->meta['kitchen_print'] ?? false,
        ];
        
        return view('pos.sales.index', compact('categories', 'paymentTypes', 'receiptSettings'));
    }

    /**
     * Ürün arama (AJAX) - barkod veya isim ile
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');
        $categoryId = $request->get('category_id');
        $branchId = session('branch_id');

        $products = Product::where('is_active', true)
            ->where('show_on_pos', true)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                          ->orWhere('barcode', $query);
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->with(['category', 'prices', 'branches' => fn($q) => $q->where('branch_id', $branchId)])
            ->limit(50)
            ->get()
            ->map(function ($product) use ($branchId) {
                $branchProduct = $product->branches->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'category_id' => $product->category_id,
                    'category' => $product->category?->name,
                    'sale_price' => $branchProduct ? (float)$branchProduct->pivot->sale_price : (float)$product->sale_price,
                    'stock_quantity' => $product->stockForBranch($branchId),
                    'vat_rate' => $product->vat_rate,
                    'unit' => $product->unit,
                    'is_service' => $product->is_service,
                    'image_url' => $product->image_url,
                    'alternative_prices' => $product->prices->map(fn($p) => [
                        'id' => $p->id,
                        'label' => $p->label,
                        'price' => (float) $p->price,
                    ])->toArray(),
                ];
            });

        return response()->json($products);
    }

    /**
     * Müşteri arama (AJAX)
     */
    public function searchCustomers(Request $request)
    {
        $query = $request->get('q', '');
        $tenantId = session('tenant_id');

        $customers = Customer::where('is_active', true)
            ->where('tenant_id', $tenantId)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'phone', 'email', 'balance', 'type']);

        return response()->json($customers);
    }

    /**
     * Satış kaydet
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', session('tenant_id'))],
            'payment_method' => ['required', 'string', 'regex:/^(cash|card|credit|mixed|transfer|other_.+)$/'],
        ]);

        try {
            $sale = $this->saleService->createSale([
                'branch_id' => session('branch_id'),
                'tenant_id' => session('tenant_id'),
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'payment_method' => $request->payment_method,
                'items' => $request->items,
                'discount' => $request->discount ?? 0,
                'cash_amount' => $request->cash_amount ?? 0,
                'card_amount' => $request->card_amount ?? 0,
                'credit_amount' => $request->credit_amount ?? 0,
                'transfer_amount' => $request->transfer_amount ?? 0,
                'staff_name' => auth()->user()->name,
                'application' => 'pos',
                'notes' => $request->notes,
                'campaign_id' => $request->campaign_id,
                'loyalty_program_id' => $request->loyalty_program_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Satış başarıyla kaydedildi.',
                'sale' => $sale->load('items'),
            ]);
        } catch (\Exception $e) {
            ActivityLog::log('sale_error', 'Satış kaydedilemedi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Satış kaydedilemedi: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Son satışlar listesi
     */
    public function recentSales(Request $request)
    {
        $sales = Sale::where('branch_id', session('branch_id'))
            ->with(['items', 'customer'])
            ->orderBy('sold_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get();

        return response()->json($sales);
    }

    /**
     * Satış detayı
     */
    public function show(Sale $sale)
    {
        if ($sale->branch_id !== (int) session('branch_id')) {
            abort(403, 'Bu satışa erişim yetkiniz yok.');
        }
        $sale->load(['items', 'customer', 'user']);
        return response()->json($sale);
    }

    /**
     * İade işlemi
     */
    public function refund(Request $request, Sale $sale)
    {
        if ($sale->branch_id !== (int) session('branch_id')) {
            abort(403, 'Bu satışa erişim yetkiniz yok.');
        }
        try {
            $result = $this->saleService->refundSale($sale->id, $request->reason);
            ActivityLog::log('refund', 'Satış iade edildi: ' . $sale->receipt_no . ' (₺' . number_format($sale->grand_total, 2) . ')', $sale);
            return response()->json([
                'success' => true,
                'message' => 'İade işlemi başarılı.',
                'sale' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Fiş numarası ile iade arama ve işleme
     */
    public function refundByReceipt(Request $request)
    {
        $request->validate([
            'receipt_no' => 'required|string',
        ]);

        $sale = Sale::where('receipt_no', $request->receipt_no)
            ->where('branch_id', session('branch_id'))
            ->first();

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Fiş bulunamadı: ' . $request->receipt_no,
            ], 404);
        }

        try {
            $result = $this->saleService->refundSale($sale->id, $request->reason);
            return response()->json([
                'success' => true,
                'message' => 'İade işlemi başarılı.',
                'sale' => $result->load('items'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Satış listesi sayfası
     */
    public function list(Request $request)
    {
        $branchId = session('branch_id');
        
        $query = Sale::where('branch_id', $branchId)
            ->with(['customer', 'user', 'items'])
            ->orderBy('sold_at', 'desc');
        
        if ($request->filled('search')) {
            $query->where('receipt_no', 'like', "%{$request->search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('sold_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sold_at', '<=', $request->end_date);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        $sales = $query->paginate(25)->withQueryString();
        
        // summaryStats: aynı filtreleri uygula
        $statsQuery = Sale::where('branch_id', $branchId)->where('status', 'completed');
        if ($request->filled('start_date')) {
            $statsQuery->whereDate('sold_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $statsQuery->whereDate('sold_at', '<=', $request->end_date);
        }
        if ($request->filled('payment_method')) {
            $statsQuery->where('payment_method', $request->payment_method);
        }

        $summaryStats = [
            'total' => (clone $statsQuery)->sum('grand_total'),
            'cash' => (clone $statsQuery)->sum('cash_amount'),
            'card' => (clone $statsQuery)->sum('card_amount'),
            'refunded' => Sale::where('branch_id', $branchId)->where('status', 'refunded')
                ->when($request->filled('start_date'), fn($q) => $q->whereDate('sold_at', '>=', $request->start_date))
                ->when($request->filled('end_date'), fn($q) => $q->whereDate('sold_at', '<=', $request->end_date))
                ->sum('grand_total'),
        ];
        
        return view('pos.sales.list', compact('sales', 'summaryStats'));
    }
}
