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
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->with('category')
            ->orderBy($request->get('sort_by', 'name'), $request->get('sort_dir', 'asc'));
        
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%")
                  ->orWhere('stock_code', 'like', "%{$s}%");
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
            $query->where('is_service', false)
                  ->whereColumn('stock_quantity', '<=', 'critical_stock');
        }

        if ($request->boolean('has_variant')) {
            $query->whereHas('variantAssignments');
        }

        if ($request->boolean('is_service')) {
            $query->where('is_service', true);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'zero') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($request->stock_status === 'positive') {
                $query->where('stock_quantity', '>', 0);
            }
        }

        if ($request->boolean('show_on_pos_only')) {
            $query->where('show_on_pos', true);
        }
        
        $products = $query->paginate(50);
        $allCategories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $categories = $this->buildCategoryTree($allCategories);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $variantTypes = ProductVariantType::where('tenant_id', session('tenant_id'))->with('values')->orderBy('sort_order')->get();
        
        return view('pos.products.index', compact('products', 'categories', 'branches', 'variantTypes'));
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

    public function store(Request $request)
    {
        $request->merge([
            'purchase_price' => ($request->purchase_price !== '' && $request->purchase_price !== null) ? $request->purchase_price : 0,
            'stock_quantity'  => ($request->stock_quantity  !== '' && $request->stock_quantity  !== null) ? $request->stock_quantity  : 0,
            'category_id'    => ($request->category_id     !== '' && $request->category_id    !== null) ? $request->category_id    : null,
        ]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
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
        $product = Product::create($data);
        
        return response()->json(['success' => true, 'product' => $product->load('category')]);
    }

    public function update(Request $request, Product $product)
    {
        $request->merge([
            'purchase_price' => ($request->purchase_price !== '' && $request->purchase_price !== null) ? $request->purchase_price : 0,
            'stock_quantity'  => ($request->stock_quantity  !== '' && $request->stock_quantity  !== null) ? $request->stock_quantity  : 0,
            'category_id'    => ($request->category_id     !== '' && $request->category_id    !== null) ? $request->category_id    : null,
        ]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
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
        
        $product->update($data);
        return response()->json(['success' => true, 'product' => $product->load('category')]);
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);
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
        $request->validate([
            'branches' => 'required|array',
            'branches.*.branch_id' => 'required|integer|exists:branches,id',
            'branches.*.enabled' => 'required|boolean',
            'branches.*.sale_price' => 'nullable|numeric|min:0',
            'branches.*.stock_quantity' => 'nullable|numeric|min:0',
        ]);

        $syncData = [];
        foreach ($request->branches as $b) {
            if ($b['enabled']) {
                $syncData[$b['branch_id']] = [
                    'sale_price' => $b['sale_price'] ?? 0,
                    'stock_quantity' => $b['stock_quantity'] ?? 0,
                ];
            }
        }

        $product->branches()->sync($syncData);

        return response()->json(['success' => true, 'message' => 'Şube bilgileri güncellendi.']);
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
            'variant_value_ids.*' => 'integer|exists:product_variant_values,id',
        ]);
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
        return response()->json(['success' => true, 'definitions' => $defs]);
    }

    /**
     * Alt ürün tanımı oluştur
     */
    public function createSubDefinition(Request $request, Product $product)
    {
        $data = $request->validate([
            'sub_product_id' => 'required|integer|exists:products,id',
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

        return response()->json(['success' => true, 'definition' => $def->load('subProduct:id,name,barcode,unit,stock_quantity')]);
    }

    /**
     * Alt ürün tanımını sil
     */
    public function deleteSubDefinition(Product $product, ProductSubDefinition $subDefinition)
    {
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
        return response()->json(['success' => true, 'message' => count($request->ids) . ' ürün silindi.']);
    }

    /**
     * Seçili ürünlere toplu kategori ata
     */
    public function bulkAssignCategory(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'category_id' => 'required|integer|exists:categories,id',
        ]);
        Product::whereIn('id', $request->ids)->update(['category_id' => $request->category_id]);
        return response()->json(['success' => true, 'message' => count($request->ids) . ' ürüne kategori atandı.']);
    }

    /**
     * Toplu fiyat güncelleme (yüzde veya sabit)
     */
    public function bulkPriceUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric',
            'field' => 'required|in:sale_price,purchase_price',
        ]);

        $products = Product::whereIn('id', $request->ids)->get();
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

    // ═══════════════════════════════════════════════════════════
    // GÖRSEL YÜKLEME
    // ═══════════════════════════════════════════════════════════

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
        $products = Product::where('is_active', true)->with('category')->orderBy('name')->get();

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
                    $cat = Category::where('name', $categoryName)->first();
                    if ($cat) $categoryId = $cat->id;
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
        $query = Product::where('is_active', true)->with('category');

        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            $childIds = Category::where('parent_id', $catId)->pluck('id');
            $allIds = collect([$catId])->merge($childIds);
            $query->whereIn('category_id', $allIds);
        }

        $products = $query->orderBy('name')->get();

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
}
