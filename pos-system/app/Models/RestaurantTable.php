<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'table_region_id',
        'table_no',
        'name',
        'capacity',
        'status',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function region()
    {
        return $this->belongsTo(TableRegion::class, 'table_region_id');
    }

    public function sessions()
    {
        return $this->hasMany(TableSession::class, 'restaurant_table_id');
    }

    public function activeSession()
    {
        return $this->hasOne(TableSession::class, 'restaurant_table_id')
            ->where('status', 'open')
            ->latestOfMany();
    }
}
