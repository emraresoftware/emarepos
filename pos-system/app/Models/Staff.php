<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'external_id',
        'name',
        'role',
        'branch_id',
        'phone',
        'email',
        'total_sales',
        'total_transactions',
        'is_active',
        'permissions',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'total_sales' => 'decimal:2',
            'total_transactions' => 'integer',
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
