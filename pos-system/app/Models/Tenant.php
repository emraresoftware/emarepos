<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'plan_id',
        'trial_ends_at',
        'billing_email',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->withPivot('is_active', 'activated_at', 'expires_at', 'config')
            ->withTimestamps();
    }
}
