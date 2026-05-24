<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'body',
        'data',
        'reference_type',
        'reference_id',
        'read_at',
        'sent_at',
        'is_push_sent',
        'priority',
        'action_url',
        'action_type',
    ];

    // Types de notification
    public const TYPE_DOCUMENT_EXPIRING = 'document_expiring';
    public const TYPE_DOCUMENT_EXPIRED = 'document_expired';
    public const TYPE_KYC_STATUS = 'kyc_status';
    public const TYPE_KYC_EXPIRING = 'kyc_expiring';
    public const TYPE_ENGAGEMENT = 'engagement';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_PAYMENT = 'payment';

    // Catégories
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_KYC = 'kyc';
    public const CATEGORY_ENGAGEMENT = 'engagement';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_PAYMENT = 'payment';

    // Priorités
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    // Types d'action
    public const ACTION_NAVIGATE = 'navigate';
    public const ACTION_OPEN_DOCUMENT = 'open_document';
    public const ACTION_OPEN_URL = 'open_url';
    public const ACTION_RENEW_DOCUMENT = 'renew_document';
    public const ACTION_COMPLETE_KYC = 'complete_kyc';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'is_push_sent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'sent_at' => now(),
            'is_push_sent' => true,
        ]);
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope pour les notifications lues
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope par catégorie
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope par priorité
     */
    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope pour les notifications urgentes
     */
    public function scopeUrgent($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Retourne les données formatées pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'priority' => $this->priority,
            'action_url' => $this->action_url,
            'action_type' => $this->action_type,
            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
