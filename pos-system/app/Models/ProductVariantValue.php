<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantValue extends Model
{
    protected $fillable = [
        'variant_type_id',
        'value',
        'sort_order',
    ];

    public function type()
    {
        return $this->belongsTo(ProductVariantType::class, 'variant_type_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variant_assignments', 'variant_value_id', 'product_id');
    }
}
