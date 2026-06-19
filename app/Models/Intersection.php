<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Intersection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'city',
        'quarter',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    /**
     * Get addresses that use this intersection as itinerary start point.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'itinerary_intersection_id');
    }

    /**
     * Scope to find intersections within a radius (in meters) of a point.
     */
    public function scopeNearby($query, float $lat, float $lng, int $radiusMeters = 500)
    {
        // Haversine formula for distance calculation
        $haversine = "(6371000 * acos(cos(radians(?))
                     * cos(radians(lat))
                     * cos(radians(lng) - radians(?))
                     + sin(radians(?))
                     * sin(radians(lat))))";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} < ?", [$lat, $lng, $lat, $radiusMeters])
            ->orderBy('distance');
    }
}
