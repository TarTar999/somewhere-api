<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentDownload extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'geo_data',
        'referrer',
        'download_type',
        'is_watermarked',
    ];

    protected $casts = [
        'geo_data' => 'array',
        'is_watermarked' => 'boolean',
    ];

    /**
     * Get the documentable model (ProofOfLocation, Invoice, etc.)
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who downloaded.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for downloads of a specific document type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('documentable_type', $type);
    }

    /**
     * Scope for downloads by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for downloads within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for watermarked downloads.
     */
    public function scopeWatermarked($query)
    {
        return $query->where('is_watermarked', true);
    }

    /**
     * Get formatted location string.
     */
    public function getLocationAttribute(): ?string
    {
        if (!$this->geo_data) {
            return null;
        }

        $parts = array_filter([
            $this->geo_data['city'] ?? null,
            $this->geo_data['country'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
