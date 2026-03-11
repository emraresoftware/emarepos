<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmPhone extends Model
{
    protected $fillable = [
        'firm_id',
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

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }
}
