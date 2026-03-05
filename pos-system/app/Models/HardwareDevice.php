<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HardwareDevice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'hardware_devices';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'type',
        'connection',
        'protocol',
        'model',
        'manufacturer',
        'vendor_id',
        'product_id_usb',
        'ip_address',
        'port',
        'serial_port',
        'baud_rate',
        'mac_address',
        'settings',
        'is_default',
        'is_active',
        'last_seen_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'port' => 'integer',
            'baud_rate' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
