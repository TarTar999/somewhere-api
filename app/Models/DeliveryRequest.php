<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DeliveryRequest extends Model
{
    protected $fillable = [
        'initiator_id',
        'recipient_id',
        'title',
        'description',
        'value',
        'currency',
        'status',
        'initiator_confirmed',
        'recipient_confirmed',
        'pickup_address_id',
        'delivery_address_id',
        'delivery_latitude',
        'delivery_longitude',
        'share_token',
        'accepted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'delivery_latitude' => 'decimal:8',
            'delivery_longitude' => 'decimal:8',
            'initiator_confirmed' => 'boolean',
            'recipient_confirmed' => 'boolean',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected $appends = ['share_url'];

    protected static function booted(): void
    {
        static::creating(function (DeliveryRequest $request) {
            if (empty($request->share_token)) {
                $request->share_token = Str::random(64);
            }
        });
    }

    // Relationships
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'pickup_address_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    // Accessors
    public function getShareUrlAttribute(): string
    {
        return config('app.url') . '/d/' . $this->share_token;
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Authorization helpers
    public function isInitiator(User $user): bool
    {
        return $this->initiator_id === $user->id;
    }

    public function isRecipient(User $user): bool
    {
        return $this->recipient_id === $user->id;
    }

    public function isParticipant(User $user): bool
    {
        return $this->isInitiator($user) || $this->isRecipient($user);
    }

    // Status transition validation
    public function canTransitionTo(string $newStatus, User $user): bool
    {
        $transitions = [
            'pending' => [
                'cancelled' => fn() => $this->isInitiator($user),
            ],
            'accepted' => [
                'in_progress' => fn() => $this->isInitiator($user),
                'cancelled' => fn() => $this->isParticipant($user),
            ],
            'in_progress' => [
                'completed' => fn() => $this->initiator_confirmed && $this->recipient_confirmed,
            ],
        ];

        if (!isset($transitions[$this->status][$newStatus])) {
            return false;
        }

        return $transitions[$this->status][$newStatus]();
    }

    // Actions
    public function accept(User $user, ?int $addressId = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        if (!$this->isPending() || $this->isInitiator($user)) {
            return false;
        }

        $this->recipient_id = $user->id;
        $this->status = 'accepted';
        $this->accepted_at = now();

        if ($addressId) {
            $this->delivery_address_id = $addressId;
        } elseif ($latitude && $longitude) {
            $this->delivery_latitude = $latitude;
            $this->delivery_longitude = $longitude;
        }

        return $this->save();
    }

    public function confirm(User $user): bool
    {
        if (!$this->isInProgress()) {
            return false;
        }

        if ($this->isInitiator($user)) {
            $this->initiator_confirmed = true;
        } elseif ($this->isRecipient($user)) {
            $this->recipient_confirmed = true;
        } else {
            return false;
        }

        if ($this->initiator_confirmed && $this->recipient_confirmed) {
            $this->status = 'completed';
            $this->completed_at = now();
        }

        return $this->save();
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('initiator_id', $user->id)
              ->orWhere('recipient_id', $user->id);
        });
    }

    public function scopeSent($query, User $user)
    {
        return $query->where('initiator_id', $user->id);
    }

    public function scopeReceived($query, User $user)
    {
        return $query->where('recipient_id', $user->id);
    }

    public function scopeWithStatus($query, array $statuses)
    {
        return $query->whereIn('status', $statuses);
    }
}
