<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Track extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'structure',
        'color',
        'is_public',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'structure' => 'array',
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Track $track) {
            if (!$track->share_token) {
                $track->share_token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'track_shares', 'track_id', 'shared_with_user_id')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Get the total distance of the track in meters
     */
    public function getDistanceAttribute(): float
    {
        $points = $this->structure ?? [];
        $totalDistance = 0;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $totalDistance += $this->haversineDistance(
                $points[$i]['lat'],
                $points[$i]['lon'],
                $points[$i + 1]['lat'],
                $points[$i + 1]['lon']
            );
        }

        return round($totalDistance, 2);
    }

    /**
     * Get the number of points in the track
     */
    public function getPointsCountAttribute(): int
    {
        return count($this->structure ?? []);
    }

    protected function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a user can view this track
     */
    public function canBeViewedBy(?User $user): bool
    {
        if ($this->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->sharedWith()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if a user can edit this track
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->sharedWith()
            ->where('users.id', $user->id)
            ->wherePivot('permission', 'edit')
            ->exists();
    }
}
