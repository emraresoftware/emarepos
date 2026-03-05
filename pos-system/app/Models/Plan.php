<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'is_active',
        'limits',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'is_active' => 'boolean',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'plan_modules')
            ->withPivot('included', 'config')
            ->withTimestamps();
    }
}
