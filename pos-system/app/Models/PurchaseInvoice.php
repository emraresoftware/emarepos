<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'branch_id', 'firm_id', 'invoice_no', 'invoice_date',
        'subtotal', 'vat_total', 'discount_total', 'grand_total',
        'status', 'payment_status', 'notes', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
