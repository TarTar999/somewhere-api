<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'identifier',
        'code',
        'type',
        'purpose',
        'expires_at',
        'verified_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function markAsVerified(): bool
    {
        return $this->update(['verified_at' => now()]);
    }

    public function scopeValid($query)
    {
        return $query->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }

    public function scopeForIdentifier($query, string $identifier, string $type = 'phone')
    {
        return $query->where('identifier', $identifier)
            ->where('type', $type);
    }

    private static function generateCode(): string
    {
        $part1 = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $part2 = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);

        return "{$part1}-{$part2}";
    }

    public static function generate(string $identifier, string $type = 'phone', string $purpose = 'verification', int $expiresInMinutes = 10): self
    {
        // Invalidate previous codes
        static::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        return static::create([
            'identifier' => $identifier,
            'code' => self::generateCode(),
            'type' => $type,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);
    }
}
