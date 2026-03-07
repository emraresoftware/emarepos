<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProductVariantType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'sort_order',
    ];

    public function values()
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_type_id')->orderBy('sort_order');
    }
}
