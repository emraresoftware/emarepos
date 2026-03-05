<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_core',
        'scope',
        'dependencies',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'dependencies' => 'array',
            'is_core' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_modules')
            ->withPivot('included', 'config')
            ->withTimestamps();
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
            ->withPivot('is_active', 'activated_at', 'expires_at', 'config')
            ->withTimestamps();
    }
}
