<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'type',
        'barcode',
        'product_id',
        'product_name',
        'transaction_code',
        'note',
        'firm_customer',
        'payment_type',
        'quantity',
        'remaining',
        'unit_price',
        'total',
        'movement_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'remaining' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'movement_date' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
