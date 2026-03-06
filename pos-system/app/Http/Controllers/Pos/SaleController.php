<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\PaymentType;
use App\Services\SaleService;
use Illuminate\Http\Request;

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
        
        return view('pos.sales.index', compact('categories', 'paymentTypes'));
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
            ->when($query, function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                          ->orWhere('barcode', $query);
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->with(['category'])
            ->limit(50)
            ->get()
            ->map(function ($product) use ($branchId) {
                $branchProduct = $product->branches()->where('branch_id', $branchId)->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'category_id' => $product->category_id,
                    'category' => $product->category?->name,
                    'sale_price' => $branchProduct ? (float)$branchProduct->pivot->sale_price : (float)$product->sale_price,
                    'stock_quantity' => $branchProduct ? (float)$branchProduct->pivot->stock_quantity : (float)$product->stock_quantity,
                    'vat_rate' => $product->vat_rate,
                    'unit' => $product->unit,
                    'is_service' => $product->is_service,
                    'image_url' => $product->image_url,
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
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
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
        $sale->load(['items', 'customer', 'user']);
        return response()->json($sale);
    }

    /**
     * İade işlemi
     */
    public function refund(Request $request, Sale $sale)
    {
        try {
            $result = $this->saleService->refundSale($sale->id, $request->reason);
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
        
        $sales = $query->paginate(25);
        
        $summaryStats = [
            'total' => Sale::where('branch_id', $branchId)->where('status', 'completed')->sum('grand_total'),
            'cash' => Sale::where('branch_id', $branchId)->where('status', 'completed')->where('payment_method', 'cash')->sum('grand_total'),
            'card' => Sale::where('branch_id', $branchId)->where('status', 'completed')->where('payment_method', 'card')->sum('grand_total'),
            'refunded' => Sale::where('branch_id', $branchId)->where('status', 'refunded')->sum('grand_total'),
        ];
        
        return view('pos.sales.list', compact('sales', 'summaryStats'));
    }
}
