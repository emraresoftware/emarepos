<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'role_id',
        'is_super_admin',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function additionalRoles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('tenant_id', 'branch_id', 'created_at');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    // ─── Authorization Helpers ───────────────────────────────

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $code): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        // Check primary role permissions
        if ($this->role && $this->role->permissions()->where('code', $code)->exists()) {
            return true;
        }

        // Check additional role permissions
        foreach ($this->additionalRoles as $role) {
            if ($role->permissions()->where('code', $code)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user's tenant has a specific module enabled.
     */
    public function hasModule(string $code): bool
    {
        return $this->tenant
            && $this->tenant->modules()
                ->where('code', $code)
                ->wherePivot('is_active', true)
                ->exists();
    }
}
