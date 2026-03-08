<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // ── İlişkiler ──────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo(null, 'model_type', 'model_id');
    }

    // ── Statik kayıt fonksiyonu ────────────────

    /**
     * Aktivite kaydı oluştur
     *
     * @param string $action   create|update|delete|refund|cancel|login|logout|print|...
     * @param string $description İnsan okunur açıklama
     * @param Model|null $model İlgili model
     * @param array|null $oldValues Eski değerler
     * @param array|null $newValues Yeni değerler
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return static::create([
            'tenant_id' => session('tenant_id'),
            'branch_id' => session('branch_id'),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'Sistem',
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
