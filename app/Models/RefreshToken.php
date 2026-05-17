<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_name',
        'device_id',
        'ip_address',
        'user_agent',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function revoke(): bool
    {
        return $this->update(['revoked_at' => now()]);
    }

    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public static function generate(User $user, ?string $deviceName = null, ?string $deviceId = null, int $expiresInDays = 30): array
    {
        $plainToken = Str::random(64);

        $refreshToken = static::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'device_name' => $deviceName,
            'device_id' => $deviceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addDays($expiresInDays),
        ]);

        return [
            'token' => $refreshToken,
            'plain_token' => $plainToken,
        ];
    }

    public static function findByPlainToken(string $plainToken): ?self
    {
        return static::where('token', hash('sha256', $plainToken))
            ->valid()
            ->first();
    }
}
