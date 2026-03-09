<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        // Eager load children counts for tree
        $tree->each(function ($group) use ($categories) {
            $group->setRelation('children', $categories->where('parent_id', $group->id)->values());
            $group->children->each(function ($sub) use ($categories) {
                $sub->setRelation('children', $categories->where('parent_id', $sub->id)->values());
            });
        });

        return view('pos.categories.index', compact('categories', 'tree'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', session('tenant_id'))],

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
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', session('tenant_id'))],
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($data);
        return response()->json(['success' => true, 'category' => $category->fresh()]);
    }

    /**
     * Kategori ağacını JSON olarak döndür (AJAX)
     * GET /categories/tree
     */
    public function tree()
    {
        $all = Category::where('tenant_id', session('tenant_id'))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'sort_order', 'is_active']);

        $tree = $this->buildTree($all);
        return response()->json($tree);
    }

    private function buildTree($categories, $parentId = null)
    {
        return $categories->where('parent_id', $parentId)->values()->map(function ($cat) use ($categories) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'parent_id' => $cat->parent_id,
                'children' => $this->buildTree($categories, $cat->id),
            ];
        });
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
