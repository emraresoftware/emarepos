<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'external_id',
        'income_expense_type_id',
        'type_name',
        'note',
        'amount',
        'payment_type',
        'date',
        'time',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'time' => 'datetime:H:i',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function type()
    {
        return $this->belongsTo(IncomeExpenseType::class, 'income_expense_type_id');
    }
}
