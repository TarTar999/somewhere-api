<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KycVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'level',
        'cni_front_path',
        'cni_back_path',
        'selfie_path',
        'video_path',
        'cni_verified',
        'selfie_verified',
        'address_verified',
        'phone_verified',
        'reviewed_by',
        'rejection_reason',
        'admin_notes',
        'reviewed_at',
        'approved_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'cni_verified' => 'boolean',
            'selfie_verified' => 'boolean',
            'address_verified' => 'boolean',
            'phone_verified' => 'boolean',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInReview(): bool
    {
        return $this->status === 'in_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function approve(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'approved_at' => now(),
            'admin_notes' => $notes,
            'expires_at' => now()->addYear(), // KYC valid for 1 year
        ]);
    }

    public function reject(User $reviewer, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'admin_notes' => $notes,
        ]);
    }

    public function getCompletionPercentage(): int
    {
        $steps = [
            !empty($this->cni_front_path),
            !empty($this->cni_back_path),
            !empty($this->selfie_path),
            $this->phone_verified,
        ];

        $completed = count(array_filter($steps));
        return (int) (($completed / count($steps)) * 100);
    }

    public function isComplete(): bool
    {
        return $this->getCompletionPercentage() === 100;
    }

    public static function getOrCreateForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'pending', 'level' => 'basic']
        );
    }
}
