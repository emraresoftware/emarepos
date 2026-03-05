<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'table_session_id',
        'sale_id',
        'order_number',
        'user_id',
        'customer_id',
        'status',
        'order_type',
        'total_items',
        'subtotal',
        'vat_total',
        'discount_total',
        'grand_total',
        'notes',
        'kitchen_notes',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'subtotal' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tableSession()
    {
        return $this->belongsTo(TableSession::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
