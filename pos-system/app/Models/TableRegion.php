<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableRegion extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'table_regions';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'sort_order',
        'is_active',
        'bg_color',
        'icon',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class, 'table_region_id');
    }
}
