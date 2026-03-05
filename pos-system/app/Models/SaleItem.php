<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'barcode',
        'quantity',
        'unit_price',
        'discount',
        'vat_rate',
        'vat_amount',
        'additional_taxes',
        'additional_tax_amount',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'vat_rate' => 'integer',
            'vat_amount' => 'decimal:2',
            'additional_taxes' => 'array',
            'additional_tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
