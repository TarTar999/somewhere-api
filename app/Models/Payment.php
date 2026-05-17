<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'address_id',
        'transaction_id',
        'external_id',
        'type',
        'amount',
        'currency',
        'status',
        'payment_link',
        'medium',
        'phone',
        'fapshi_response',
        'failure_reason',
        'webhook_received_at',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fapshi_response' => 'array',
            'webhook_received_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->external_id)) {
                $payment->external_id = 'SW-' . strtoupper(Str::random(12));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function proofOfLocation(): HasOne
    {
        return $this->hasOne(ProofOfLocation::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'expired', 'cancelled']);
    }

    public function markAsSuccessful(array $fapshiResponse = []): void
    {
        $this->update([
            'status' => 'successful',
            'paid_at' => now(),
            'webhook_received_at' => now(),
            'fapshi_response' => array_merge($this->fapshi_response ?? [], $fapshiResponse),
        ]);
    }

    public function markAsFailed(string $reason, array $fapshiResponse = []): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'webhook_received_at' => now(),
            'fapshi_response' => array_merge($this->fapshi_response ?? [], $fapshiResponse),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'webhook_received_at' => now(),
        ]);
    }
}
