<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'registration_number',
        'tax_id',
        'email',
        'phone',
        'logo_path',
        'description',
        'address',
        'city',
        'country',
        'status',
        'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Company $company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name) . '-' . Str::random(6);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status', 'invited_by', 'invitation_token', 'invitation_expires_at', 'joined_at'])
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 'active');
    }

    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'manager');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class)
            ->where('status', 'active')
            ->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CompanyPayment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function proofOfLocations(): HasMany
    {
        return $this->hasMany(ProofOfLocation::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null && $this->activeSubscription->isActive();
    }

    public function canCreateDocument(): bool
    {
        if (!$this->isActive() || !$this->hasActiveSubscription()) {
            return false;
        }

        return $this->getRemainingDocuments() > 0;
    }

    public function getRemainingDocuments(): int
    {
        $subscription = $this->activeSubscription;
        if (!$subscription) {
            return 0;
        }

        $usedDocuments = $this->documents()
            ->whereBetween('period_start', [
                $subscription->current_period_start,
                $subscription->current_period_end,
            ])
            ->count();

        return max(0, $subscription->documents_per_month - $usedDocuments);
    }

    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    public function canAddMember(): bool
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        return $this->getMemberCount() < $this->activeSubscription->max_members;
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);
    }

    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    public function getUserRole(User $user): ?string
    {
        $pivot = $this->users()->where('user_id', $user->id)->first();
        return $pivot?->pivot->role;
    }

    public function isUserAdmin(User $user): bool
    {
        return $this->getUserRole($user) === 'admin';
    }

    public function isUserManager(User $user): bool
    {
        return in_array($this->getUserRole($user), ['admin', 'manager']);
    }
}
