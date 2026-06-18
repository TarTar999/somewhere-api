<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'pin_code',
        'has_pin_code',
        'sex',
        'nui_number',
        'cni_number',
        'cni_expiration_date',
        'avatar_path',
        'lottie_avatar',
        'signature',
        'is_admin',
        'deletion_requested_at',
        'deletion_scheduled_at',
        'deletion_reason',
        'current_company_id',
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin_code' => 'hashed',
            'has_pin_code' => 'boolean',
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

    public function getInitialsAttribute(): string
    {
        $firstName = $this->first_name ?? '';
        $lastName = $this->last_name ?? '';

        if ($firstName || $lastName) {
            $initials = '';
            if ($firstName) {
                $initials .= mb_strtoupper(mb_substr($firstName, 0, 1));
            }
            if ($lastName) {
                $initials .= mb_strtoupper(mb_substr($lastName, 0, 1));
            }
            return $initials;
        }

        // Fallback to name field
        $name = $this->attributes['name'] ?? '';
        if ($name) {
            $parts = explode(' ', $name);
            $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
            if (count($parts) > 1) {
                $initials .= mb_strtoupper(mb_substr(end($parts), 0, 1));
            }
            return $initials;
        }

        return '?';
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

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->appNotifications()->whereNull('read_at');
    }

    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->unreadNotifications()->count();
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function activeDeviceTokens(): HasMany
    {
        return $this->deviceTokens()->where('is_active', true);
    }

    // Company relationships
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role', 'status', 'invited_by', 'invitation_token', 'invitation_expires_at', 'joined_at'])
            ->withTimestamps();
    }

    public function activeCompanies(): BelongsToMany
    {
        return $this->companies()->wherePivot('status', 'active');
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function hasCompanyAccess(): bool
    {
        $company = $this->currentCompany;
        if (!$company) {
            return false;
        }

        return $company->isActive() && $company->hasActiveSubscription();
    }

    public function getCompanyRole(?Company $company = null): ?string
    {
        $company = $company ?? $this->currentCompany;
        if (!$company) {
            return null;
        }

        $pivot = $this->companies()->where('company_id', $company->id)->first();
        return $pivot?->pivot->role;
    }

    public function isCompanyAdmin(?Company $company = null): bool
    {
        return $this->getCompanyRole($company) === 'admin';
    }

    public function isCompanyManager(?Company $company = null): bool
    {
        return in_array($this->getCompanyRole($company), ['admin', 'manager']);
    }

    public function canCreateCompanyDocument(): bool
    {
        if (!$this->hasCompanyAccess()) {
            return false;
        }

        return $this->currentCompany->canCreateDocument();
    }

    public function switchCompany(Company $company): bool
    {
        $membership = $this->companies()
            ->where('company_id', $company->id)
            ->wherePivot('status', 'active')
            ->first();

        if (!$membership) {
            return false;
        }

        $this->update(['current_company_id' => $company->id]);
        return true;
    }

    // PIN Code Authentication Methods
    public function canAuthenticateWithPassword(): bool
    {
        return !empty($this->password);
    }

    public function canAuthenticateWithPin(): bool
    {
        return $this->has_pin_code && !empty($this->pin_code);
    }

    public function getAuthMethods(): array
    {
        return [
            'password' => $this->canAuthenticateWithPassword(),
            'pin_code' => $this->canAuthenticateWithPin(),
        ];
    }

    public function needsPinSetup(): bool
    {
        return $this->canAuthenticateWithPassword() && !$this->canAuthenticateWithPin();
    }
}
