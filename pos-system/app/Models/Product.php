<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

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
        'is_active',
        'is_service',
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
            'vat_rate' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
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

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
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
}
