<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyProgram extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'loyalty_programs';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'points_per_currency',
        'currency_per_point',
        'min_redeem_points',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_per_currency' => 'decimal:2',
            'currency_per_point' => 'decimal:4',
            'min_redeem_points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function loyaltyPoints()
    {
        return $this->hasMany(LoyaltyPoint::class);
    }
}
