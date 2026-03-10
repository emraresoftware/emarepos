<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\ActivityLog;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');

        $query = StockMovement::with('product')
            ->where('branch_id', $branchId)
            ->orderBy('movement_date', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%")
                  ->orWhere('transaction_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('movement_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('movement_date', '<=', $request->end_date);
        }

        $movements = $query->paginate(50)->withQueryString();

        $products = Product::where('is_active', true)
            ->with(['branches' => fn ($q) => $q->where('branch_id', $branchId)])
            ->get();

        $criticalStock = $products
            ->filter(function ($product) use ($branchId) {
                return $product->critical_stock > 0 && $product->stockForBranch($branchId) <= $product->critical_stock;
            })
            ->sortBy(fn ($product) => $product->stockForBranch($branchId))
            ->take(20)
            ->values();

        $stats = [
            'total_products' => $products->count(),
            'low_stock' => $criticalStock->count(),
            'total_stock_value' => $products->sum(fn ($product) => $product->stockForBranch($branchId) * (float) $product->purchase_price),
            'movements_today' => StockMovement::where('branch_id', $branchId)->whereDate('movement_date', Carbon::today())->count(),
        ];

        return view('pos.stock.index', compact('movements', 'criticalStock', 'stats', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'type' => 'required|string|in:purchase,sale,return,adjustment,transfer,waste',
            'quantity' => 'required|numeric',
            'unit_price' => 'nullable|numeric|min:0',
            'firm_customer' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'payment_type' => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $data['tenant_id'] = session('tenant_id');
        $data['branch_id'] = session('branch_id');
        $data['barcode'] = $product->barcode;
        $data['product_name'] = $product->name;
        $data['total'] = ($data['unit_price'] ?? 0) * abs($data['quantity']);
        $data['movement_date'] = Carbon::now();
        $data['transaction_code'] = 'SM-' . date('YmdHis') . '-' . rand(100, 999);

        $movement = DB::transaction(function () use ($data, $product) {
            // Stok güncelle
            $product = Product::where('id', $product->id)->lockForUpdate()->first();
            if (in_array($data['type'], ['purchase', 'return'], true)) {
                $remaining = $product->adjustStockForBranch((int) session('branch_id'), abs((float) $data['quantity']));
            } elseif ($data['type'] === 'adjustment') {
                $remaining = $product->adjustStockForBranch((int) session('branch_id'), (float) $data['quantity']);
            } else {
                $remaining = $product->adjustStockForBranch((int) session('branch_id'), -abs((float) $data['quantity']));
            }

            $data['remaining'] = $remaining;
            return StockMovement::create($data);
        });

        ActivityLog::log('stock_movement', 'Stok hareketi: ' . $data['type'] . ' - ' . $product->name . ' (' . $data['quantity'] . ')', $movement);

        return response()->json(['success' => true, 'movement' => $movement]);
    }
}
