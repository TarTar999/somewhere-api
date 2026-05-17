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
        'signature',
        'verification_status',
        'video_path',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy' => 'float',
            'honor_declaration' => 'boolean',
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
}
