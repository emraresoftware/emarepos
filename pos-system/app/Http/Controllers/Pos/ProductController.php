<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->with('category')
            ->orderBy('name');
        
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%");
            });
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->boolean('low_stock')) {
            $query->where('is_service', false)
                  ->whereColumn('stock_quantity', '<=', 'critical_stock');
        }
        
        $products = $query->paginate(50);
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        
        return view('pos.products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'required|integer',
            'stock_quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
        ]);
        
        $data['tenant_id'] = session('tenant_id');
        $product = Product::create($data);
        
        return response()->json(['success' => true, 'product' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'required|integer',
            'stock_quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
        ]);
        
        $product->update($data);
        return response()->json(['success' => true, 'product' => $product]);
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);
        return response()->json(['success' => true]);
    }
}
