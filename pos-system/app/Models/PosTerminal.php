<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosTerminal extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'receipt_printer_id',
        'kitchen_printer_id',
        'cash_drawer_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function receiptPrinter()
    {
        return $this->belongsTo(HardwareDevice::class, 'receipt_printer_id');
    }

    public function kitchenPrinter()
    {
        return $this->belongsTo(HardwareDevice::class, 'kitchen_printer_id');
    }

    public function cashDrawer()
    {
        return $this->belongsTo(HardwareDevice::class, 'cash_drawer_id');
    }
}
