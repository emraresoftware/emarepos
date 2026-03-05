<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with('product')
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

        $movements = $query->paginate(50);

        // Kritik stok ürünleri
        $criticalStock = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'critical_stock')
            ->where('critical_stock', '>', 0)
            ->orderBy('stock_quantity')
            ->limit(20)
            ->get();

        $stats = [
            'total_products' => Product::where('is_active', true)->count(),
            'low_stock' => $criticalStock->count(),
            'total_stock_value' => Product::where('is_active', true)->selectRaw('SUM(stock_quantity * purchase_price) as val')->value('val') ?? 0,
            'movements_today' => StockMovement::whereDate('movement_date', Carbon::today())->count(),
        ];

        return view('pos.stock.index', compact('movements', 'criticalStock', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|string|in:purchase,sale,return,adjustment,transfer,waste',
            'quantity' => 'required|numeric',
            'unit_price' => 'nullable|numeric|min:0',
            'firm_customer' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'payment_type' => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $data['tenant_id'] = session('tenant_id');
        $data['barcode'] = $product->barcode;
        $data['product_name'] = $product->name;
        $data['total'] = ($data['unit_price'] ?? 0) * abs($data['quantity']);
        $data['movement_date'] = Carbon::now();
        $data['transaction_code'] = 'SM-' . date('YmdHis') . '-' . rand(100, 999);

        // Stok güncelle
        if (in_array($data['type'], ['purchase', 'return'])) {
            $product->increment('stock_quantity', abs($data['quantity']));
        } else {
            $product->decrement('stock_quantity', abs($data['quantity']));
        }

        $data['remaining'] = $product->fresh()->stock_quantity;
        $movement = StockMovement::create($data);

        return response()->json(['success' => true, 'movement' => $movement]);
    }
}
