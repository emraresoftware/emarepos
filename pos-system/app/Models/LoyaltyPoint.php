<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    use HasFactory;

    protected $table = 'loyalty_points';

    protected $fillable = [
        'customer_id',
        'loyalty_program_id',
        'points',
        'type',
        'description',
        'sale_id',
        'campaign_id',
        'balance_after',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_after' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function loyaltyProgram()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
