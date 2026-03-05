<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeExpenseType extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'income_expense_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'direction',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────

    public function incomes()
    {
        return $this->hasMany(Income::class, 'income_expense_type_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'income_expense_type_id');
    }
}
