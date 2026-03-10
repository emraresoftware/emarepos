<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $numericDefaults = [
                'purchase_price' => 0,
                'sale_price' => 0,
                'stock_quantity' => 0,
                'critical_stock' => 0,
                'vat_rate' => 0,
            ];

            foreach ($numericDefaults as $field => $default) {
                if ($product->{$field} === null || $product->{$field} === '') {
                    $product->{$field} = $default;
                }
            }

            if ($product->unit === null || $product->unit === '') {
                $product->unit = 'Adet';
            }

            if ($product->show_on_pos === null) {
                $product->show_on_pos = true;
            }

            if ($product->is_active === null) {
                $product->is_active = true;
            }

            if ($product->is_service === null) {
                $product->is_service = false;
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'external_id',
        'barcode',
        'name',
        'description',
        'category_id',
        'service_category_id',
        'variant_type',
        'parent_id',
        'unit',
        'purchase_price',
        'sale_price',
        'vat_rate',
        'additional_taxes',
        'stock_quantity',
        'critical_stock',
        'image_url',
        'country_of_origin',
        'is_active',
        'is_service',
        'stock_code',
        'sort_order',
        'show_on_pos',
        'firm_id',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'critical_stock' => 'decimal:2',
            'additional_taxes' => 'array',
            'is_active' => 'boolean',
            'is_service' => 'boolean',
            'show_on_pos' => 'boolean',
            'vat_rate' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_product')
            ->withPivot('stock_quantity', 'sale_price')
            ->withTimestamps();
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function variantAssignments()
    {
        return $this->belongsToMany(ProductVariantValue::class, 'product_variant_assignments', 'product_id', 'variant_value_id');
    }

    public function subDefinitions()
    {
        return $this->hasMany(ProductSubDefinition::class, 'parent_product_id');
    }

    public function parentDefinitions()
    {
        return $this->hasMany(ProductSubDefinition::class, 'sub_product_id');
    }

    // ─── Accessors ───────────────────────────────────────────

    /**
     * Get effective price, optionally for a specific branch.
     */
    public function getEffectivePriceAttribute(): string
    {
        return $this->sale_price;
    }

    /**
     * Get the effective price for a specific branch.
     */
    public function effectivePriceForBranch(?int $branchId = null): string
    {
        if ($branchId) {
            $branchProduct = $this->branches()->where('branch_id', $branchId)->first();
            if ($branchProduct && $branchProduct->pivot->sale_price > 0) {
                return $branchProduct->pivot->sale_price;
            }
        }

        return $this->sale_price;
    }

    public function branchStockRecord(int $branchId)
    {
        return $this->branches()->where('branch_id', $branchId)->first();
    }

    public function branchStockEnabled(): bool
    {
        return $this->branches()->exists();
    }

    public function stockForBranch(int $branchId): float
    {
        $branchProduct = $this->branchStockRecord($branchId);
        if ($branchProduct) {
            return (float) $branchProduct->pivot->stock_quantity;
        }

        return $this->branchStockEnabled() ? 0.0 : (float) $this->stock_quantity;
    }

    public function syncStockQuantityFromBranches(): float
    {
        $total = (float) ($this->branches()->sum('branch_product.stock_quantity') ?? 0);
        $this->forceFill(['stock_quantity' => $total])->save();

        return $total;
    }

    public function adjustStockForBranch(int $branchId, float $delta): float
    {
        $branchProduct = $this->branchStockRecord($branchId);
        $hasAnyBranchStock = $branchProduct !== null || $this->branchStockEnabled();
        $currentStock = $branchProduct
            ? (float) $branchProduct->pivot->stock_quantity
            : ($hasAnyBranchStock ? 0.0 : (float) $this->stock_quantity);

        $newStock = $currentStock + $delta;
        if ($newStock < 0) {
            throw new \Exception("'{$this->name}' için yeterli stok yok (Mevcut: {$currentStock}).");
        }

        if ($branchProduct) {
            $this->branches()->updateExistingPivot($branchId, [
                'stock_quantity' => $newStock,
            ]);
        } else {
            $this->branches()->attach($branchId, [
                'stock_quantity' => $newStock,
                'sale_price' => $this->sale_price,
            ]);
        }

        $this->syncStockQuantityFromBranches();

        return $newStock;
    }

    public function setStockForBranch(int $branchId, float $quantity): float
    {
        if ($quantity < 0) {
            throw new \Exception('Stok miktarı negatif olamaz.');
        }

        $branchProduct = $this->branchStockRecord($branchId);

        if ($branchProduct) {
            $this->branches()->updateExistingPivot($branchId, [
                'stock_quantity' => $quantity,
            ]);
        } else {
            $this->branches()->attach($branchId, [
                'stock_quantity' => $quantity,
                'sale_price' => $this->sale_price,
            ]);
        }

        $this->syncStockQuantityFromBranches();

        return $quantity;
    }
}
