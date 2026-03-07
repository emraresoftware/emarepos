<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProductSubDefinition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'parent_product_id',
        'sub_product_id',
        'multiplier',
        'apply_to_branches',
    ];

    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:2',
            'apply_to_branches' => 'boolean',
        ];
    }

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(Product::class, 'sub_product_id');
    }
}
