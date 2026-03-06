<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'tenant_id',
        'session_key',
        'user_name',
        'category',
        'priority',
        'message',
        'page_url',
        'status',
        'admin_reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    // Sabit etiketler
    const CATEGORY_LABELS = [
        'bug'        => '🐛 Hata',
        'suggestion' => '💡 Öneri',
        'question'   => '❓ Soru',
        'other'      => '💬 Diğer',
    ];

    const PRIORITY_LABELS = [
        'low'      => 'Düşük',
        'normal'   => 'Normal',
        'high'     => 'Yüksek',
        'critical' => 'Kritik',
    ];

    const STATUS_LABELS = [
        'open'        => 'Açık',
        'in_progress' => 'İnceleniyor',
        'resolved'    => 'Çözüldü',
        'closed'      => 'Kapatıldı',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
