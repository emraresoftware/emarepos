<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'code', 'title', 'status', 'notes', 'user_id', 'applied_at',
    ];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
