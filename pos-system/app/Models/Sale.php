<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'external_id',
        'receipt_no',
        'branch_id',
        'terminal_id',
        'customer_id',
        'user_id',
        'payment_method',
        'total_items',
        'subtotal',
        'vat_total',
        'additional_tax_total',
        'discount_total',
        'service_fee',
        'grand_total',
        'discount',
        'cash_amount',
        'card_amount',
        'credit_amount',
        'transfer_amount',
        'status',
        'notes',
        'staff_name',
        'application',
        'note',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'subtotal' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'additional_tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'card_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'transfer_amount' => 'decimal:2',
            'sold_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function terminal()
    {
        return $this->belongsTo(PosTerminal::class, 'terminal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
