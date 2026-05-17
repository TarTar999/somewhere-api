<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domiciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_id',
        'invited_by',
        'name',
        'role',
        'status',
        'invitation_token',
        'token_expires_at',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAddress($query, int $addressId)
    {
        return $query->where('address_id', $addressId);
    }

    // Helper methods
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isResident(): bool
    {
        return $this->role === 'resident';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canManageResidents(): bool
    {
        return $this->isApproved() && ($this->isOwner() || $this->isResident());
    }

    public function isTokenValid(): bool
    {
        return $this->invitation_token
            && $this->token_expires_at
            && $this->token_expires_at->isFuture();
    }

    /**
     * Generate invitation token for QR code
     */
    public static function generateInvitationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create a pending domiciliation with invitation token
     */
    public static function createInvitation(int $addressId, int $invitedBy, int $expiresInMinutes = 30): self
    {
        return self::create([
            'user_id' => $invitedBy, // Temporary, will be updated when scanned
            'address_id' => $addressId,
            'invited_by' => $invitedBy,
            'status' => 'pending',
            'invitation_token' => self::generateInvitationToken(),
            'token_expires_at' => now()->addMinutes($expiresInMinutes),
        ]);
    }
}
