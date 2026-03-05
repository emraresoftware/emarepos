<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HardwareDriver extends Model
{
    use HasFactory;

    protected $table = 'hardware_drivers';

    protected $fillable = [
        'device_type',
        'manufacturer',
        'model',
        'vendor_id',
        'product_id',
        'protocol',
        'connections',
        'features',
        'specs',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'connections' => 'array',
            'features' => 'array',
            'specs' => 'array',
        ];
    }
}
