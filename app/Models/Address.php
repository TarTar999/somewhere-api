<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'street_id',
        'street_number',
        'distance_on_street',
        'street_side',
        'sw_address',
        'display_name',
        'latitude',
        'longitude',
        'accuracy',
        'house_type',
        'home_status',
        'quarter',
        'sub_quarter',
        'lieu_dit',
        'description',
        'official_address',
        'way_code',
        'way_display_name',
        'honor_declaration',
        'resident_name',
        'is_non_habitation',
        'signature',
        'verification_status',
        'video_path',
        // Itinerary fields
        'itinerary',
        'itinerary_street_id',
        'itinerary_description',
        'itinerary_distance',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy' => 'float',
            'honor_declaration' => 'boolean',
            'is_non_habitation' => 'boolean',
            'itinerary' => 'array',
        ];
    }

    protected $appends = ['lat_lon', 'localization', 'way', 'coordinates'];

    public function getLatLonAttribute(): array
    {
        return [(float) $this->latitude, (float) $this->longitude];
    }

    public function getCoordinatesAttribute(): array
    {
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }

    public function getLocalizationAttribute(): array
    {
        return [
            'quarter' => $this->quarter,
            'sousQuarter' => $this->sub_quarter,
            'lieuDit' => $this->lieu_dit,
            'officialAddress' => $this->official_address,
        ];
    }

    public function getWayAttribute(): ?array
    {
        // Use street relation if available
        if ($this->street_id && $this->relationLoaded('street') && $this->street) {
            return [
                'id' => $this->street->id,
                'osmId' => $this->street->osm_id,
                'code' => $this->street->code,
                'displayName' => $this->street->display_name,
                'communeName' => $this->street->commune_name,
                'communeNumber' => $this->street->commune_number,
                'structure' => $this->street->structure,
                'streetNumber' => $this->street_number,
                'streetSide' => $this->street_side,
            ];
        }

        // Fallback to legacy fields
        if (!$this->way_code) {
            return null;
        }
        return [
            'code' => $this->way_code,
            'displayName' => $this->way_display_name,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }

    public function itineraryStreet(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'itinerary_street_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'address_collection')
            ->withPivot('order')
            ->withTimestamps();
    }

    public function domiciliations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Domiciliation::class);
    }

    public function domiciliatedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'domiciliations')
            ->withPivot(['name', 'role', 'status', 'is_primary', 'invited_by'])
            ->withTimestamps();
    }

    public function approvedResidents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'domiciliations')
            ->wherePivot('status', 'approved')
            ->withPivot(['name', 'role', 'is_primary'])
            ->withTimestamps();
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} < ?", [$lat, $lng, $lat, $radiusKm])
            ->orderBy('distance');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    /**
     * Get the share URL for this address
     */
    public function getShareUrl(): string
    {
        $baseUrl = config('share.base_url', config('app.url'));
        return "{$baseUrl}/share/address/{$this->id}";
    }

    /**
     * Get the share URL using SW address
     */
    public function getShareUrlBySw(): string
    {
        $baseUrl = config('share.base_url', config('app.url'));
        $encodedSwAddress = urlencode($this->sw_address);
        return "{$baseUrl}/share/address/sw/{$encodedSwAddress}";
    }

    /**
     * Check if address has a custom itinerary
     */
    public function hasItinerary(): bool
    {
        return !empty($this->itinerary) && is_array($this->itinerary) && count($this->itinerary) > 0;
    }

    /**
     * Get formatted itinerary data
     */
    public function getItineraryDataAttribute(): ?array
    {
        if (!$this->hasItinerary()) {
            return null;
        }

        return [
            'points' => $this->itinerary,
            'pointsCount' => count($this->itinerary),
            'description' => $this->itinerary_description,
            'distance' => $this->itinerary_distance,
            'destinationStreet' => $this->relationLoaded('itineraryStreet') && $this->itineraryStreet
                ? [
                    'id' => $this->itineraryStreet->id,
                    'code' => $this->itineraryStreet->code,
                    'displayName' => $this->itineraryStreet->display_name,
                ]
                : null,
        ];
    }

    /**
     * Calculate itinerary distance from points to address (in meters)
     * Includes distance from all itinerary points plus distance to the address
     */
    public function calculateItineraryDistance(): ?int
    {
        if (!$this->hasItinerary() || count($this->itinerary) < 1) {
            return null;
        }

        $distance = 0;
        $points = $this->itinerary;

        // Calculate distance between itinerary points
        for ($i = 0; $i < count($points) - 1; $i++) {
            $distance += $this->haversineDistance(
                $points[$i]['lat'],
                $points[$i]['lng'],
                $points[$i + 1]['lat'],
                $points[$i + 1]['lng']
            );
        }

        // Add distance from last itinerary point to the address
        $lastPoint = end($points);
        $addressLat = (float) $this->latitude;
        $addressLng = (float) $this->longitude;

        $distance += $this->haversineDistance(
            $lastPoint['lat'],
            $lastPoint['lng'],
            $addressLat,
            $addressLng
        );

        return (int) round($distance);
    }

    /**
     * Calculate distance between two points using Haversine formula (returns meters)
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
