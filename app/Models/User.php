<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'sex',
        'nui_number',
        'cni_number',
        'cni_expiration_date',
        'avatar_path',
        'is_admin',
        'deletion_requested_at',
        'deletion_scheduled_at',
        'deletion_reason',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'cni_expiration_date' => 'date',
            'is_admin' => 'boolean',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_at' => 'datetime',
        ];
    }

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        return $this->attributes['name'] ?? '';
    }

    public function getNameAttribute($value): string
    {
        if ($value) {
            return $value;
        }
        return $this->getFullNameAttribute();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSettings::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'owner_id');
    }

    public function sharedCollections(): HasMany
    {
        return $this->hasMany(SharedCollection::class, 'shared_with_user_id');
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function initiatedDeliveryRequests(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'initiator_id');
    }

    public function receivedDeliveryRequests(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'recipient_id');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function sharedTracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'track_shares', 'shared_with_user_id', 'track_id')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function kycVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    public function proofOfLocations(): HasMany
    {
        return $this->hasMany(ProofOfLocation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function webAccessTokens(): HasMany
    {
        return $this->hasMany(WebAccessToken::class);
    }

    public function isKycVerified(): bool
    {
        $kyc = $this->kycVerification;
        return $kyc && $kyc->isApproved() && !$kyc->isExpired();
    }

    public function hasPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    public function getOrCreateSettings(): UserSettings
    {
        return $this->settings ?? $this->settings()->create([
            'language' => 'fr',
            'unit' => 'metric',
            'notifications' => 'enabled',
            'map_type' => 'GoogleMap',
        ]);
    }

    public function domiciliations(): HasMany
    {
        return $this->hasMany(Domiciliation::class);
    }

    public function domiciliatedAddresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'domiciliations')
            ->withPivot(['name', 'role', 'status', 'is_primary', 'invited_by'])
            ->withTimestamps();
    }

    public function primaryDomiciliation(): ?Domiciliation
    {
        return $this->domiciliations()
            ->where('is_primary', true)
            ->where('status', 'approved')
            ->first();
    }
}
