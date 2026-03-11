<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPhone extends Model
{
    protected $fillable = [
        'customer_id',
        'phone',
        'type',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
