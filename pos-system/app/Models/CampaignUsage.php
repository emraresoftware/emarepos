<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignUsage extends Model
{
    use HasFactory;

    protected $table = 'campaign_usages';

    protected $fillable = [
        'campaign_id',
        'customer_id',
        'sale_id',
        'discount_applied',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'decimal:2',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
