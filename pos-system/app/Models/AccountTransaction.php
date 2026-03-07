<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'account_transactions';

    protected $fillable = [
        'tenant_id',
        'external_id',
        'customer_id',
        'firm_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }
}
