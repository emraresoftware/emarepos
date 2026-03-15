<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Firm extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'external_id',
        'firm_group_id',
        'name',
        'tax_number',
        'tax_office',
        'phone',
        'email',
        'address',
        'city',
        'balance',
        'credit_limit',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────
    public function group()
    {
        return $this->belongsTo(FirmGroup::class, 'firm_group_id');
    }

    public function phones()
    {
        return $this->hasMany(FirmPhone::class)->orderByDesc('is_primary');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'firm_customer', 'name');
    }
}
