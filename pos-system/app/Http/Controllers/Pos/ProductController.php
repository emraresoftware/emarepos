<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariantType;
use App\Models\ProductVariantValue;
use App\Models\ProductSubDefinition;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Firm;
use App\Models\FilterTemplate;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;

class ProductController extends Controller
{
    private function currentBranch(): ?Branch
    {
        return Branch::find(session('branch_id'));
    }

    private function canEditPrices(?Branch $branch = null): bool
    {
        $branch = $branch ?? $this->currentBranch();
        $isCenter = (bool) ($branch?->settings['is_center'] ?? false);
        $locked = (bool) ($branch?->settings['price_edit_locked'] ?? false);
        $hasPerm = auth()->user()->is_super_admin || auth()->user()->hasPermission('products.price_edit');

        return $hasPerm && ($isCenter || !$locked);
    }

    public function index(Request $request)
    {
        $branchId = (int) session('branch_id');
        $allowedSorts = ['name', 'sale_price', 'stock_quantity', 'purchase_price', 'created_at', 'barcode', 'stock_code'];
        $sortBy  = in_array($request->get('sort_by'), $allowedSorts) ? $request->get('sort_by') : 'name';
        $sortDir = $request->get('sort_dir') === 'desc' ? 'desc' : 'asc';

        $query = Product::where('is_active', true)
            ->with(['category', 'firm']);

        $stockExpression = null;
        if ($branchId > 0) {
            $stockExpression = $this->effectiveStockExpression();
            $query->select('products.*')->selectRaw("{$stockExpression} as effective_stock_quantity", [$branchId]);
        }
        
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%")
                  ->orWhere('stock_code', 'like', "%{$s}%")
                  ->orWhere('variant_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('firm_id')) {
            $query->where('firm_id', $request->firm_id);
        }

        if ($request->filled('variant_type_id')) {
            $query->whereHas('variantAssignments', function($q) use ($request) {
                $vtId = $request->variant_type_id;
                $q->whereHas('type', fn($tq) => $tq->where('id', $vtId));
            });
        }
        
        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            $childIds = Category::where('parent_id', $catId)->pluck('id');
            $grandChildIds = Category::whereIn('parent_id', $childIds)->pluck('id');
            $allIds = collect([$catId])->merge($childIds)->merge($grandChildIds);
            $query->whereIn('category_id', $allIds);
        }
        
        if ($request->boolean('low_stock')) {
            $query->where('is_service', false);

            if ($stockExpression !== null) {
                $query->whereRaw("{$stockExpression} <= products.critical_stock", [$branchId]);
            } else {
                $query->whereColumn('stock_quantity', '<=', 'critical_stock');
            }
        }

        if ($request->boolean('has_variant')) {
            $query->whereHas('variantAssignments');
        }

        if ($request->boolean('is_service')) {
            $query->where('is_service', true);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'zero') {
                if ($stockExpression !== null) {
                    $query->whereRaw("{$stockExpression} <= 0", [$branchId]);
                } else {
                    $query->where('stock_quantity', '<=', 0);
                }
            } elseif ($request->stock_status === 'positive') {
                if ($stockExpression !== null) {
                    $query->whereRaw("{$stockExpression} > 0", [$branchId]);
                } else {
                    $query->where('stock_quantity', '>', 0);
                }
            }
        }

        if ($request->boolean('show_on_pos_only')) {
            $query->where('show_on_pos', true);
        }
        
        if ($sortBy === 'stock_quantity' && $stockExpression !== null) {
            $query->orderBy('effective_stock_quantity', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $products = $query->paginate(50)->withQueryString();
        $this->hydrateEffectiveStock($products->getCollection(), $branchId);
        $allCategories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $categories = $this->buildCategoryTree($allCategories);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $variantTypes = ProductVariantType::where('tenant_id', session('tenant_id'))->with('values')->orderBy('sort_order')->get();
        $firms = Firm::where('is_active', true)->orderBy('name')->get();
        $filterTemplates = FilterTemplate::where('user_id', auth()->id())->where('page', 'products')->orderBy('sort_order')->get();

        $currentBranch = $this->currentBranch();
        $priceEditAllowed = $this->canEditPrices($currentBranch);
        $priceEditLocked = (bool) ($currentBranch?->settings['price_edit_locked'] ?? false);
        $isCenter = (bool) ($currentBranch?->settings['is_center'] ?? false);
        
        return view('pos.products.index', compact('products', 'categories', 'branches', 'variantTypes', 'firms', 'filterTemplates', 'priceEditAllowed', 'priceEditLocked', 'isCenter'));
    }

    /**
     * Recursive kategori ağacı oluştur (sınırsız derinlik)
     */
    private function buildCategoryTree($categories, $parentId = null)
    {
        return $categories->where('parent_id', $parentId)->values()->map(function ($cat) use ($categories) {
            $cat->setRelation('children', $this->buildCategoryTree($categories, $cat->id));
            return $cat;
        });
    }

    private function effectiveStockExpression(): string
    {
        return "CASE
            WHEN EXISTS (
                SELECT 1
                FROM branch_product AS bp_any
                WHERE bp_any.product_id = products.id
            ) THEN COALESCE((
                SELECT bp_current.stock_quantity
                FROM branch_product AS bp_current
                WHERE bp_current.product_id = products.id
                  AND bp_current.branch_id = ?
                LIMIT 1
            ), NULL)
            ELSE products.stock_quantity
        END";
    }

    private function hydrateEffectiveStock($products, int $branchId): void
    {
        if ($branchId <= 0) {
            return;
        }

        $products->transform(function ($product) {
            if (isset($product->effective_stock_quantity)) {
                $product->stock_quantity = (float) $product->effective_stock_quantity;
            }

            return $product;
        });
    }

    private function hydrateSubProductStocks($definitions, int $branchId): void
    {
        if ($branchId <= 0) {
            return;
        }

        $definitions->each(function ($definition) use ($branchId) {
            if ($definition->subProduct) {
                $definition->subProduct->stock_quantity = $definition->subProduct->stockForBranch($branchId);
            }
        });
    }

    public function store(Request $request)
    {
        $branchId = (int) session('branch_id');
        $canEditPrices = $this->canEditPrices();
        if (!$canEditPrices) {
            return response()->json(['success' => false, 'message' => 'Fiyat düzenleme yetkiniz yok.'], 403);
        }

        $request->merge([
            'purchase_price' => ($request->purchase_price !== '' && $request->purchase_price !== null) ? $request->purchase_price : 0,
            'stock_quantity'  => ($request->stock_quantity  !== '' && $request->stock_quantity  !== null) ? $request->stock_quantity  : 0,
            'critical_stock'  => ($request->critical_stock  !== '' && $request->critical_stock  !== null) ? $request->critical_stock  : 0,
            'category_id'    => ($request->category_id     !== '' && $request->category_id    !== null) ? $request->category_id    : null,
            'firm_id'        => ($request->firm_id         !== '' && $request->firm_id        !== null) ? $request->firm_id        : null,
        ]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', session('tenant_id'))],
            'firm_id' => ['nullable', 'integer', Rule::exists('firms', 'id')->where('tenant_id', session('tenant_id'))],
            'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'required|integer',
            'stock_quantity' => 'nullable|numeric|min:0',
            'critical_stock' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'stock_code' => 'nullable|string|max:100',
            'show_on_pos' => 'nullable|boolean',
            'is_service' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
        ]);
        
        $data['tenant_id'] = session('tenant_id');
        $data['show_on_pos'] = $data['show_on_pos'] ?? true;
        $data['critical_stock'] = $data['critical_stock'] ?? 0;
        $stockQuantity = (float) ($data['stock_quantity'] ?? 0);
        $data['stock_quantity'] = $stockQuantity;
        $product = Product::create($data);

        if ($branchId > 0) {
            $product->setStockForBranch($branchId, $stockQuantity);
            $product->refresh();
        }
        
        return response()->json(['success' => true, 'product' => $product->load('category')]);
    }

    public function update(Request $request, Product $product)
    {
        $branchId = (int) session('branch_id');
        $canEditPrices = $this->canEditPrices();

        $request->merge([
            'stock_quantity'  => ($request->stock_quantity  !== '' && $request->stock_quantity  !== null) ? $request->stock_quantity  : 0,
            'critical_stock'  => ($request->critical_stock  !== '' && $request->critical_stock  !== null) ? $request->critical_stock  : 0,
            'category_id'    => ($request->category_id     !== '' && $request->category_id    !== null) ? $request->category_id    : null,
            'firm_id'        => ($request->firm_id         !== '' && $request->firm_id        !== null) ? $request->firm_id        : null,
        ]);
        if ($canEditPrices) {
            $request->merge([
                'purchase_price' => ($request->purchase_price !== '' && $request->purchase_price !== null) ? $request->purchase_price : 0,
                'sale_price' => ($request->sale_price !== '' && $request->sale_price !== null) ? $request->sale_price : 0,
            ]);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', session('tenant_id'))],
            'firm_id' => ['nullable', 'integer', Rule::exists('firms', 'id')->where('tenant_id', session('tenant_id'))],
            'sale_price' => $canEditPrices ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'required|integer',
            'stock_quantity' => 'nullable|numeric|min:0',
            'critical_stock' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'stock_code' => 'nullable|string|max:100',
            'show_on_pos' => 'nullable|boolean',
            'is_service' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
        ];

        $data = $request->validate($rules);
        
        $data['critical_stock'] = $data['critical_stock'] ?? 0;
        $stockQuantity = (float) ($data['stock_quantity'] ?? 0);

        if (!$canEditPrices) {
            unset($data['sale_price'], $data['purchase_price']);
        }

        if ($branchId > 0) {
            unset($data['stock_quantity']);
        } else {
            $data['stock_quantity'] = $stockQuantity;
        }

        $product->update($data);

        if ($branchId > 0) {
            $product->setStockForBranch($branchId, $stockQuantity);
            $product->refresh();
        }

        return response()->json(['success' => true, 'product' => $product->load('category')]);
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);
        ActivityLog::log('delete', 'Ürün silindi: ' . $product->name, $product);
        return response()->json(['success' => true]);
    }

    public function history(Product $product)
    {
        $movements = StockMovement::where('product_id', $product->id)
            ->orderByDesc('movement_date')
            ->take(100)
            ->get(['type','transaction_code','note','firm_customer','payment_type','quantity','remaining','unit_price','total','movement_date']);
        return response()->json(['success' => true, 'movements' => $movements]);
    }

    /**
     * Ürünün şube bilgilerini getir
     */
    public function getBranches(Product $product)
    {
        $productBranches = $product->branches()->get()->keyBy('id');
        $allBranches = Branch::where('is_active', true)->orderBy('name')->get();

        $data = $allBranches->map(function ($branch) use ($productBranches) {
            $pivot = $productBranches->get($branch->id);
            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'enabled' => $pivot !== null,
                'sale_price' => $pivot ? $pivot->pivot->sale_price : 0,
                'stock_quantity' => $pivot ? $pivot->pivot->stock_quantity : 0,
            ];
        });

        return response()->json(['success' => true, 'branches' => $data]);
    }

    /**
     * Ürünün şube bilgilerini güncelle (pivot sync)
     */
    public function syncBranches(Request $request, Product $product)
    {
        $canEditPrices = $this->canEditPrices();
        $request->validate([
            'branches' => 'required|array',
            'branches.*.branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', session('tenant_id'))],
            'branches.*.enabled' => 'required|boolean',
            'branches.*.sale_price' => 'nullable|numeric|min:0',
            'branches.*.stock_quantity' => 'nullable|numeric|min:0',
        ]);

        $syncData = [];
        $existing = $product->branches()->get()->keyBy('id');
        foreach ($request->branches as $b) {
            if ($b['enabled']) {
                $existingPivot = $existing->get($b['branch_id']);
                $salePrice = $canEditPrices
                    ? ($b['sale_price'] ?? 0)
                    : ($existingPivot ? $existingPivot->pivot->sale_price : $product->sale_price);
                $syncData[$b['branch_id']] = [
                    'sale_price' => $salePrice,
                    'stock_quantity' => $b['stock_quantity'] ?? 0,
                ];
            }
        }

        $product->branches()->sync($syncData);
        $product->syncStockQuantityFromBranches();

        return response()->json(['success' => true, 'message' => 'Şube bilgileri güncellendi.']);
    }

    /**
     * Toplu şube atama (seçili ürünler için)
     */
    public function bulkAssignBranches(Request $request)
    {
        if (!$this->canEditPrices()) {
            return response()->json(['success' => false, 'message' => 'Fiyat düzenleme yetkiniz yok.'], 403);
        }
        $data = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'branches' => 'required|array|min:1',
            'branches.*.branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', session('tenant_id'))],
            'branches.*.enabled' => 'required|boolean',
            'default_sale_price' => 'nullable|numeric|min:0',
            'default_stock_quantity' => 'nullable|numeric|min:0',
            'apply_to_existing' => 'nullable|boolean',
        ]);

        $productIds = collect($data['product_ids'])->unique()->values();
        $branchIds = collect($data['branches'])->filter(fn ($b) => $b['enabled'])->pluck('branch_id')->unique()->values();
        $applyToExisting = (bool) ($data['apply_to_existing'] ?? false);
        $defaultSale = $data['default_sale_price'] ?? null;
        $defaultStock = $data['default_stock_quantity'] ?? null;

        if ($branchIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'En az bir şube seçiniz.'], 422);
        }

        foreach ($productIds as $productId) {
            $existing = DB::table('branch_product')
                ->where('product_id', $productId)
                ->whereIn('branch_id', $branchIds)
                ->get()
                ->keyBy('branch_id');

            foreach ($branchIds as $branchId) {
                $current = $existing->get($branchId);
                $salePrice = $defaultSale;
                $stockQty = $defaultStock;

                if ($current) {
                    if ($salePrice === null || !$applyToExisting) {
                        $salePrice = $current->sale_price;
                    }
                    if ($stockQty === null || !$applyToExisting) {
                        $stockQty = $current->stock_quantity;
                    }
                }

                DB::table('branch_product')->updateOrInsert(
                    ['branch_id' => $branchId, 'product_id' => $productId],
                    [
                        'sale_price' => $salePrice ?? 0,
                        'stock_quantity' => $stockQty ?? 0,
                        'updated_at' => now(),
                        'created_at' => $current ? $current->created_at : now(),
                    ]
                );
            }

            $product = Product::find($productId);
            if ($product) {
                $product->syncStockQuantityFromBranches();
            }
        }

        return response()->json(['success' => true, 'message' => 'Şubelere atama tamamlandı.']);
    }

    /**
     * Ürünün alternatif fiyatlarını getir
     */
    public function getPrices(Product $product)
    {
        $prices = $product->prices()->get();
        return response()->json(['success' => true, 'prices' => $prices]);
    }

    /**
     * Ürüne yeni alternatif fiyat ekle
     */
    public function storePrice(Request $request, Product $product)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $price = ProductPrice::create([
            'tenant_id' => session('tenant_id'),
            'product_id' => $product->id,
            'label' => $data['label'],
            'price' => $data['price'],
            'is_active' => true,
            'sort_order' => $product->prices()->count(),
        ]);

        return response()->json(['success' => true, 'price' => $price]);
    }

    /**
     * Alternatif fiyat güncelle
     */
    public function updatePrice(Request $request, Product $product, ProductPrice $price)
    {
        if ($price->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Bu fiyat bu ürüne ait değil.'], 403);
        }
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $price->update($data);
        return response()->json(['success' => true, 'price' => $price]);
    }

    /**
     * Alternatif fiyat sil
     */
    public function destroyPrice(Product $product, ProductPrice $price)
    {
        if ($price->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Bu fiyat bu ürüne ait değil.'], 403);
        }
        $price->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════
    // VARYANT YÖNETİMİ
    // ═══════════════════════════════════════════════════════════

    /**
     * Varyant tiplerini listele (Renk, Beden, Boyut vb.)
     */
    public function variantTypes()
    {
        $types = ProductVariantType::where('tenant_id', session('tenant_id'))
            ->with('values')
            ->orderBy('sort_order')
            ->get();
        return response()->json(['success' => true, 'types' => $types]);
    }

    /**
     * Yeni varyant tipi oluştur
     */
    public function createVariantType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);
        $type = ProductVariantType::create([
            'tenant_id' => session('tenant_id'),
            'name' => $data['name'],
            'sort_order' => ProductVariantType::where('tenant_id', session('tenant_id'))->count(),
        ]);
        return response()->json(['success' => true, 'type' => $type->load('values')]);
    }

    /**
     * Varyant tipini sil
     */
    public function deleteVariantType(ProductVariantType $variantType)
    {
        if ($variantType->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $variantType->values()->delete();
        $variantType->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Varyant tipine yeni değer ekle
     */
    public function createVariantValue(Request $request, ProductVariantType $variantType)
    {
        $data = $request->validate([
            'value' => 'required|string|max:100',
        ]);
        $val = ProductVariantValue::create([
            'variant_type_id' => $variantType->id,
            'value' => $data['value'],
            'sort_order' => $variantType->values()->count(),
        ]);
        return response()->json(['success' => true, 'value' => $val]);
    }

    /**
     * Varyant değerini sil
     */
    public function deleteVariantValue(ProductVariantValue $variantValue)
    {
        // Kiracı doğrulaması (parent type üzerinden)
        if (! $variantValue->type || $variantValue->type->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        DB::table('product_variant_assignments')->where('variant_value_id', $variantValue->id)->delete();
        $variantValue->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Ürüne varyant ata / güncelle
     */
    public function syncProductVariants(Request $request, Product $product)
    {
        $request->validate([
            'variant_value_ids' => 'required|array',
            'variant_value_ids.*' => 'integer',
        ]);

        $variantIds = collect($request->variant_value_ids)->filter()->unique()->values();
        $gecerliAdet = ProductVariantValue::whereIn('id', $variantIds)
            ->whereHas('type', fn ($query) => $query->where('tenant_id', session('tenant_id')))
            ->count();

        if ($gecerliAdet !== $variantIds->count()) {
            return response()->json(['success' => false, 'message' => 'Geçersiz varyant seçimi.'], 422);
        }

        $product->variantAssignments()->sync($request->variant_value_ids);
        return response()->json(['success' => true, 'variants' => $product->variantAssignments()->with('type')->get()]);
    }

    /**
     * Ürünün varyantlarını getir
     */
    public function getProductVariants(Product $product)
    {
        $variants = $product->variantAssignments()->with('type')->get();
        return response()->json(['success' => true, 'variants' => $variants]);
    }

    // ═══════════════════════════════════════════════════════════
    // ALT ÜRÜN TANIMI (Koli - Paket - Adet)
    // ═══════════════════════════════════════════════════════════

    /**
     * Alt ürün tanımlarını getir
     */
    public function getSubDefinitions(Product $product)
    {
        $defs = $product->subDefinitions()->with('subProduct:id,name,barcode,unit,stock_quantity')->get();
        $this->hydrateSubProductStocks($defs, (int) session('branch_id'));

        return response()->json(['success' => true, 'definitions' => $defs]);
    }

    /**
     * Alt ürün tanımı oluştur
     */
    public function createSubDefinition(Request $request, Product $product)
    {
        $data = $request->validate([
            'sub_product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', session('tenant_id'))],
            'multiplier' => 'required|numeric|min:0.01',
            'apply_to_branches' => 'nullable|boolean',
        ]);

        $def = ProductSubDefinition::create([
            'tenant_id' => session('tenant_id'),
            'parent_product_id' => $product->id,
            'sub_product_id' => $data['sub_product_id'],
            'multiplier' => $data['multiplier'],
            'apply_to_branches' => $data['apply_to_branches'] ?? false,
        ]);

        $def->load('subProduct:id,name,barcode,unit,stock_quantity');
        $this->hydrateSubProductStocks(collect([$def]), (int) session('branch_id'));

        return response()->json(['success' => true, 'definition' => $def]);
    }

    /**
     * Alt ürün tanımını sil
     */
    public function deleteSubDefinition(Product $product, ProductSubDefinition $subDefinition)
    {
        if ($subDefinition->tenant_id !== (int) session('tenant_id') || $subDefinition->parent_product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Bu alt ürün tanımı bu ürüne ait değil.'], 403);
        }

        $subDefinition->delete();
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════════════
    // TOPLU İŞLEMLER
    // ═══════════════════════════════════════════════════════════

    /**
     * Seçili ürünleri toplu sil
     */
    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        Product::whereIn('id', $request->ids)->update(['is_active' => false]);
        ActivityLog::log('bulk_delete', count($request->ids) . ' ürün toplu silindi');
        return response()->json(['success' => true, 'message' => count($request->ids) . ' ürün silindi.']);
    }

    /**
     * Seçili ürünlere toplu kategori ata
     */
    public function bulkAssignCategory(Request $request)
    {
        $tenantId = session('tenant_id');
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
        ]);
        // Sadece bu tenant'ın ürünleri güncellensin
        Product::whereIn('id', $request->ids)->where('tenant_id', $tenantId)->update(['category_id' => $request->category_id]);
        return response()->json(['success' => true, 'message' => count($request->ids) . ' ürüne kategori atandı.']);
    }

    /**
     * Toplu fiyat güncelleme (yüzde veya sabit)
     */
    public function bulkPriceUpdate(Request $request)
    {
        if (!$this->canEditPrices()) {
            return response()->json(['success' => false, 'message' => 'Fiyat düzenleme yetkiniz yok.'], 403);
        }
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric',
            'field' => 'required|in:sale_price,purchase_price',
        ]);

        // Sadece bu tenant'ın ürünleri güncellensin
        $products = Product::whereIn('id', $request->ids)->where('tenant_id', session('tenant_id'))->get();
        foreach ($products as $product) {
            if ($request->type === 'percent') {
                $newPrice = $product->{$request->field} * (1 + $request->value / 100);
            } else {
                $newPrice = $request->value;
            }
            $product->update([$request->field => max(0, round($newPrice, 2))]);
        }

        return response()->json(['success' => true, 'message' => count($request->ids) . ' ürünün fiyatı güncellendi.']);
    }

    /**
     * Seçili ürünlerin şube bazlı mevcut fiyatlarını getir
     * GET /products/branch-prices?ids[]=1&ids[]=2
     */
    public function getBranchPricesForProducts(Request $request)
    {
        $tenantId = session('tenant_id');
        $ids = array_filter((array) $request->get('ids', []), 'is_numeric');

        $products = Product::whereIn('id', $ids)
            ->where('tenant_id', $tenantId)
            ->with('branches')
            ->get(['id', 'name', 'sale_price', 'purchase_price']);

        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $productData = $products->map(function ($p) use ($branches) {
            $pivotMap = $p->branches->keyBy('id');
            return [
                'id'           => $p->id,
                'name'         => $p->name,
                'sale_price'   => $p->sale_price,
                'branch_prices' => $branches->mapWithKeys(function ($b) use ($pivotMap) {
                    $pivot = $pivotMap->get($b->id);
                    return [$b->id => $pivot ? $pivot->pivot->sale_price : null];
                }),
            ];
        });

        return response()->json([
            'success'  => true,
            'products' => $productData,
            'branches' => $branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }

    /**
     * Şube bazlı toplu fiyat güncelleme
     * POST /products/bulk-branch-price-update
     */
    public function bulkBranchPriceUpdate(Request $request)
    {
        if (!$this->canEditPrices()) {
            return response()->json(['success' => false, 'message' => 'Fiyat düzenleme yetkiniz yok.'], 403);
        }
        $request->validate([
            'ids'              => 'required|array',
            'ids.*'            => 'integer',
            'updates'          => 'required|array',
            'updates.*.branch_id' => 'required|integer',
            'updates.*.type'   => 'nullable|in:percent,fixed',
            'updates.*.value'  => 'nullable|numeric',
            'updates.*.product_id' => 'nullable|integer',
            'updates.*.price'  => 'nullable|numeric|min:0',
        ]);

        $tenantId  = session('tenant_id');
        $products  = Product::whereIn('id', $request->ids)->where('tenant_id', $tenantId)->get();
        $branchIds = Branch::where('tenant_id', $tenantId)->where('is_active', true)->pluck('id')->toArray();
        $updatedCount = 0;

        foreach ($products as $product) {
            foreach ($request->updates as $upd) {
                if (!empty($upd['product_id']) && (int) $upd['product_id'] !== (int) $product->id) {
                    continue;
                }

                $branchId = (int) $upd['branch_id'];
                if (!in_array($branchId, $branchIds)) continue;

                // Mevcut pivot satırını bul
                $pivot = DB::table('branch_product')
                    ->where('product_id', $product->id)
                    ->where('branch_id', $branchId)
                    ->first();

                $currentPrice = $pivot ? (float) $pivot->sale_price : (float) $product->sale_price;

                if (array_key_exists('price', $upd) && $upd['price'] !== null && $upd['price'] !== '') {
                    $newPrice = (float) $upd['price'];
                } elseif (($upd['type'] ?? null) === 'percent') {
                    $newPrice = $currentPrice * (1 + (float) $upd['value'] / 100);
                } else {
                    $newPrice = (float) $upd['value'];
                }
                $newPrice = max(0, round($newPrice, 2));

                DB::table('branch_product')->updateOrInsert(
                    ['product_id' => $product->id, 'branch_id' => $branchId],
                    ['sale_price' => $newPrice, 'updated_at' => now(), 'created_at' => now()]
                );
                $updatedCount++;
            }
        }

        return response()->json(['success' => true, 'message' => "{$updatedCount} şube-ürün fiyatı güncellendi."]);
    }

    /**
     * Ürün rapor özeti — raporlar modalı için
     * GET /products/report-summary
     */
    public function reportSummary(Request $request)
    {
        $tenantId = session('tenant_id');
        $branchId = (int) session('branch_id');

        $products = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['category', 'branches'])
            ->get();

        $lowStock      = $products->filter(fn ($p) => !$p->is_service && $p->stock_quantity <= $p->critical_stock && $p->stock_quantity > 0);
        $zeroStock     = $products->filter(fn ($p) => !$p->is_service && $p->stock_quantity <= 0);
        $totalStockVal = $products->sum(fn ($p) => $p->stock_quantity * $p->purchase_price);
        $totalSaleVal  = $products->sum(fn ($p) => $p->stock_quantity * $p->sale_price);

        $byCat = $products->groupBy(fn ($p) => optional($p->category)->name ?? 'Kategori Yok')
            ->map(fn ($grp, $name) => [
                'name'    => $name,
                'count'   => $grp->count(),
                'stock'   => $grp->sum('stock_quantity'),
                'value'   => round($grp->sum(fn ($p) => $p->stock_quantity * $p->purchase_price), 2),
            ])->values()->sortByDesc('count')->values();

        // En çok satan 10 ürün (son 30 gün)
        $topSelling = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.tenant_id', $tenantId)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.total_price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Şube bazlı stok özeti
        $branchStocks = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->withCount(['products as product_count'])
            ->get(['id', 'name'])
            ->map(function ($b) use ($tenantId) {
                $rows = DB::table('branch_product')
                    ->join('products', 'products.id', '=', 'branch_product.product_id')
                    ->where('products.tenant_id', $tenantId)
                    ->where('branch_product.branch_id', $b->id)
                    ->select(DB::raw('SUM(branch_product.stock_quantity) as total_stock'),
                             DB::raw('SUM(branch_product.stock_quantity * products.purchase_price) as total_value'))
                    ->first();
                return [
                    'id'          => $b->id,
                    'name'        => $b->name,
                    'total_stock' => (int) ($rows->total_stock ?? 0),
                    'total_value' => round($rows->total_value ?? 0, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'summary' => [
                'total_products'    => $products->count(),
                'active_products'   => $products->where('is_active', true)->count(),
                'total_stock_value' => round($totalStockVal, 2),
                'total_sale_value'  => round($totalSaleVal, 2),
                'potential_profit'  => round($totalSaleVal - $totalStockVal, 2),
                'low_stock_count'   => $lowStock->count(),
                'zero_stock_count'  => $zeroStock->count(),
            ],
            'low_stock_products' => $lowStock->take(10)->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name,
                'stock' => $p->stock_quantity, 'critical' => $p->critical_stock,
                'category' => optional($p->category)->name,
            ])->values(),
            'zero_stock_products' => $zeroStock->take(10)->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'category' => optional($p->category)->name,
            ])->values(),
            'by_category'    => $byCat,
            'top_selling'    => $topSelling,
            'branch_stocks'  => $branchStocks,
        ]);
    }

    /**
     * Ürüne görsel yükle
     */
    public function uploadImage(Request $request, Product $product)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
            Storage::disk('public')->delete($product->image_url);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image_url' => $path]);

        return response()->json(['success' => true, 'image_url' => asset('storage/' . $path)]);
    }

    // ═══════════════════════════════════════════════════════════
    // EXCEL İÇE / DIŞA AKTARMA
    // ═══════════════════════════════════════════════════════════

    /**
     * Ürünleri CSV olarak dışa aktar
     */
    public function exportExcel()
    {
        $branchId = (int) session('branch_id');
        $query = Product::where('is_active', true)->with('category');

        if ($branchId > 0) {
            $stockExpression = $this->effectiveStockExpression();
            $query->select('products.*')->selectRaw("{$stockExpression} as effective_stock_quantity", [$branchId]);
        }

        $products = $query->orderBy('name')->get();
        $this->hydrateEffectiveStock($products, $branchId);

        $csv = "Barkod;Stok Kodu;Ürün Adı;Kategori;Birim;Alış Fiyatı;Satış Fiyatı;KDV%;Stok;Kritik Stok;Hizmet;POS Göster\n";
        foreach ($products as $p) {
            $csv .= implode(';', [
                $p->barcode ?? '',
                $p->stock_code ?? '',
                '"' . str_replace('"', '""', $p->name) . '"',
                $p->category ? '"' . str_replace('"', '""', $p->category->name) . '"' : '',
                $p->unit ?? 'Adet',
                $p->purchase_price,
                $p->sale_price,
                $p->vat_rate,
                $p->stock_quantity,
                $p->critical_stock ?? 0,
                $p->is_service ? 'Evet' : 'Hayır',
                $p->show_on_pos ? 'Evet' : 'Hayır',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="urunler_' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * CSV dosyasından toplu ürün içe aktar
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $rows = array_map(function ($line) {
            return str_getcsv($line, ';');
        }, file($file->getRealPath()));

        $header = array_shift($rows);
        $created = 0;
        $updated = 0;
        $errors = [];

        // N+1 önlemi: tüm kategorileri önceden yükle (ad → id eşlemesi)
        $categoryCache = Category::where('tenant_id', session('tenant_id'))
            ->pluck('id', 'name')
            ->toArray();

        foreach ($rows as $i => $row) {
            if (count($row) < 7) {
                $errors[] = 'Satır ' . ($i + 2) . ': Yetersiz sütun';
                continue;
            }

            try {
                $barcode = trim($row[0]);
                $stockCode = trim($row[1]);
                $name = trim($row[2], '" ');
                $categoryName = trim($row[3], '" ');
                $unit = trim($row[4]) ?: 'Adet';
                $purchasePrice = floatval(str_replace(',', '.', $row[5]));
                $salePrice = floatval(str_replace(',', '.', $row[6]));
                $vatRate = isset($row[7]) ? intval($row[7]) : 20;
                $stockQty = isset($row[8]) ? floatval(str_replace(',', '.', $row[8])) : 0;
                $criticalStock = isset($row[9]) ? floatval(str_replace(',', '.', $row[9])) : 0;

                if (empty($name)) {
                    $errors[] = 'Satır ' . ($i + 2) . ': Ürün adı boş';
                    continue;
                }

                $categoryId = null;
                if (!empty($categoryName)) {
                    $categoryId = $categoryCache[$categoryName] ?? null;
                }

                $existing = null;
                if (!empty($barcode)) {
                    $existing = Product::where('barcode', $barcode)->where('is_active', true)->first();
                }

                $data = [
                    'name' => $name,
                    'barcode' => $barcode ?: null,
                    'stock_code' => $stockCode ?: null,
                    'category_id' => $categoryId,
                    'unit' => $unit,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'vat_rate' => $vatRate,
                    'stock_quantity' => $stockQty,
                    'critical_stock' => $criticalStock,
                ];

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    $data['tenant_id'] = session('tenant_id');
                    Product::create($data);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = 'Satır ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'message' => "$created yeni ürün eklendi, $updated ürün güncellendi." . (count($errors) > 0 ? ' ' . count($errors) . ' satırda hata.' : ''),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ÜRÜN ÖZET DÖKÜMÜ / BARKOD ETİKETİ
    // ═══════════════════════════════════════════════════════════

    /**
     * Ürün özet dökümü (yazdırılabilir)
     */
    public function summary(Request $request)
    {
        $branchId = (int) session('branch_id');
        $query = Product::where('is_active', true)->with('category');

        if ($branchId > 0) {
            $stockExpression = $this->effectiveStockExpression();
            $query->select('products.*')->selectRaw("{$stockExpression} as effective_stock_quantity", [$branchId]);
        }

        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            $childIds = Category::where('parent_id', $catId)->pluck('id');
            $allIds = collect([$catId])->merge($childIds);
            $query->whereIn('category_id', $allIds);
        }

        $products = $query->orderBy('name')->get();
        $this->hydrateEffectiveStock($products, $branchId);

        $summary = [
            'total_products' => $products->count(),
            'total_stock_value' => $products->sum(fn ($p) => $p->stock_quantity * $p->purchase_price),
            'total_sale_value' => $products->sum(fn ($p) => $p->stock_quantity * $p->sale_price),
            'low_stock_count' => $products->filter(fn ($p) => !$p->is_service && $p->stock_quantity <= $p->critical_stock)->count(),
            'zero_stock_count' => $products->filter(fn ($p) => !$p->is_service && $p->stock_quantity <= 0)->count(),
            'service_count' => $products->filter(fn ($p) => $p->is_service)->count(),
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'barcode' => $p->barcode,
                'stock_code' => $p->stock_code,
                'name' => $p->name,
                'category' => $p->category?->name,
                'unit' => $p->unit,
                'purchase_price' => $p->purchase_price,
                'sale_price' => $p->sale_price,
                'stock_quantity' => $p->stock_quantity,
                'critical_stock' => $p->critical_stock,
                'stock_value' => round($p->stock_quantity * $p->purchase_price, 2),
            ]),
        ];

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    /**
     * Barkod etiketi verisi üret
     */
    public function generateLabels(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'quantity' => 'nullable|integer|min:1|max:100',
        ]);

        $qty = $request->quantity ?? 1;
        $products = Product::whereIn('id', $request->ids)->get(['id', 'name', 'barcode', 'sale_price', 'stock_code']);

        $labels = [];
        foreach ($products as $p) {
            for ($i = 0; $i < $qty; $i++) {
                $labels[] = [
                    'name' => $p->name,
                    'barcode' => $p->barcode ?? $p->stock_code ?? '',
                    'price' => number_format($p->sale_price, 2, ',', '.') . ' ₺',
                ];
            }
        }

        return response()->json(['success' => true, 'labels' => $labels]);
    }

    /**
     * Ürün sıralama güncelle (drag & drop)
     */
    public function updateSortOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            Product::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    // ── Filtre Şablon CRUD ─────────────────────────────────

    public function saveFilterTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'filters' => 'required|array',
        ]);

        $template = FilterTemplate::create([
            'tenant_id' => session('tenant_id'),
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'page' => 'products',
            'filters' => $data['filters'],
        ]);

        return response()->json(['success' => true, 'template' => $template]);
    }

    public function deleteFilterTemplate(FilterTemplate $template)
    {
        if ($template->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok'], 403);
        }
        $template->delete();
        return response()->json(['success' => true]);
    }

    // ── Barkod Üretici ─────────────────────────────────────────

    /**
     * Benzersiz EAN-13 barkod üretir
     */
    public function generateBarcode()
    {
        $tenantId = session('tenant_id');
        $attempts = 0;
        do {
            // 12 rasgele rakam + EAN-13 check digit
            $digits = '';
            for ($i = 0; $i < 12; $i++) {
                $digits .= random_int(0, 9);
            }
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $digits[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            $check = (10 - ($sum % 10)) % 10;
            $barcode = $digits . $check;
            $attempts++;
        } while (
            Product::where('barcode', $barcode)->exists() && $attempts < 20
        );

        return response()->json(['success' => true, 'barcode' => $barcode]);
    }

    /**
     * Tek ürünün detaylı hareketlerini döner (Ürünleri Yönet sekmesi)
     */
    public function getProductDetail(Request $request)
    {
        $barcode = $request->query('barcode');
        $productId = $request->query('product_id');

        $query = Product::where('is_active', true)
            ->with(['category', 'firm', 'branches', 'prices']);

        if ($barcode) {
            $query->where('barcode', $barcode);
        } elseif ($productId) {
            $query->where('id', $productId);
        } else {
            return response()->json(['success' => false, 'message' => 'Barkod veya ürün ID gerekli'], 422);
        }

        $product = $query->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Ürün bulunamadı']);
        }

        $movements = StockMovement::where('product_id', $product->id)
            ->orderByDesc('movement_date')
            ->take(100)
            ->get(['type','transaction_code','note','firm_customer','payment_type','quantity','remaining','unit_price','total','movement_date']);

        $branchData = $this->getBranches($product)->getData(true)['branches'] ?? [];

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'stock_code' => $product->stock_code,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'vat_rate' => $product->vat_rate,
                'stock_quantity' => $product->stock_quantity,
                'critical_stock' => $product->critical_stock,
                'unit' => $product->unit,
                'country_of_origin' => $product->country_of_origin,
                'show_on_pos' => $product->show_on_pos,
                'is_service' => $product->is_service,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'firm_id' => $product->firm_id,
                'firm_name' => $product->firm?->name,
                'image_url' => $product->image_url ? asset('storage/' . $product->image_url) : null,
            ],
            'movements' => $movements,
            'branches' => $branchData,
        ]);
    }
}
