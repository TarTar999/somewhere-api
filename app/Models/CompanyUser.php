<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    protected $table = 'company_user';

    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'user_id',
        'invited_by',
        'role',
        'status',
        'invitation_token',
        'invitation_expires_at',
        'joined_at',
    ];

    protected $casts = [
        'invitation_expires_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_MEMBER = 'member';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public static array $roles = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_MEMBER,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canManageMembers(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    public function canViewAllAddresses(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    public function canManageSubscription(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canRemoveMembers(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canChangeRoles(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isInvitationExpired(): bool
    {
        if (!$this->invitation_expires_at) {
            return false;
        }

        return $this->invitation_expires_at->isPast();
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'joined_at' => now(),
            'invitation_token' => null,
            'invitation_expires_at' => null,
        ]);
    }
}
