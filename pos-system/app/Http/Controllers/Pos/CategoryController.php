<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount('products')->orderBy('sort_order');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->get();
        $tree = $categories->whereNull('parent_id')->values();

        return view('pos.categories.index', compact('categories', 'tree'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['tenant_id'] = session('tenant_id');
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $category = Category::create($data);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($data);
        return response()->json(['success' => true, 'category' => $category->fresh()]);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return response()->json(['success' => false, 'message' => 'Bu kategoride ürünler var, silinemez.'], 422);
        }
        $category->delete();
        return response()->json(['success' => true]);
    }
}
