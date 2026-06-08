<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_id',
        'transaction_id',
        'external_id',
        'amount',
        'currency',
        'status',
        'payment_link',
        'medium',
        'phone',
        'fapshi_response',
        'failure_reason',
        'paid_at',
    ];

    protected $casts = [
        'fapshi_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CompanySubscription::class, 'subscription_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsSuccessful(array $fapshiResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESSFUL,
            'paid_at' => now(),
            'fapshi_response' => $fapshiResponse,
        ]);
    }

    public function markAsFailed(string $reason, array $fapshiResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'fapshi_response' => $fapshiResponse,
        ]);
    }
}
