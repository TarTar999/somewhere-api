<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WebAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'type',
        'resource_id',
        'ip_address',
        'user_agent',
        'usage_count',
        'max_usage',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
            'max_usage' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($token) {
            if (empty($token->token)) {
                $token->token = Str::random(64);
            }
            if (empty($token->expires_at)) {
                $token->expires_at = now()->addMinutes(30); // Default 30 min expiry
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        if ($this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_usage !== -1 && $this->usage_count >= $this->max_usage) {
            return false;
        }

        return true;
    }

    public function use(?string $ipAddress = null, ?string $userAgent = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $this->increment('usage_count');
        $this->update([
            'used_at' => now(),
            'ip_address' => $ipAddress ?? $this->ip_address,
            'user_agent' => $userAgent ?? $this->user_agent,
        ]);

        return true;
    }

    public static function createForUser(
        User $user,
        string $type,
        ?int $resourceId = null,
        int $validityMinutes = 30,
        int $maxUsage = -1 // -1 for unlimited
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'resource_id' => $resourceId,
            'expires_at' => now()->addMinutes($validityMinutes),
            'max_usage' => $maxUsage,
        ]);
    }

    public static function findValidToken(string $token): ?self
    {
        $webToken = self::where('token', $token)->first();

        if (!$webToken || !$webToken->isValid()) {
            return null;
        }

        return $webToken;
    }
}
