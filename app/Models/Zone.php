<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Zone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_zone_id',
        'name',
        'slug',
        'description',
        'zone_type',
        'center_lat',
        'center_lng',
        'radius_meters',
        'polygon_coordinates',
        'fill_color',
        'fill_opacity',
        'stroke_color',
        'stroke_width',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
        'fill_opacity' => 'decimal:2',
        'polygon_coordinates' => 'array',
        'metadata' => 'array',
    ];

    public const TYPE_CIRCLE = 'circle';
    public const TYPE_POLYGON = 'polygon';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Zone $zone) {
            if (empty($zone->slug)) {
                $zone->slug = Str::slug($zone->name);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'parent_zone_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Zone::class, 'parent_zone_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'zone_labels')
            ->withTimestamps();
    }

    public function isCircle(): bool
    {
        return $this->zone_type === self::TYPE_CIRCLE;
    }

    public function isPolygon(): bool
    {
        return $this->zone_type === self::TYPE_POLYGON;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if a point is contained within this zone.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->isCircle()) {
            return $this->containsPointCircle($lat, $lng);
        }

        return $this->containsPointPolygon($lat, $lng);
    }

    /**
     * Check if a point is within a circle zone using Haversine formula.
     */
    protected function containsPointCircle(float $lat, float $lng): bool
    {
        if (!$this->center_lat || !$this->center_lng || !$this->radius_meters) {
            return false;
        }

        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($this->center_lat);
        $lat2 = deg2rad($lat);
        $deltaLat = deg2rad($lat - $this->center_lat);
        $deltaLng = deg2rad($lng - $this->center_lng);

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $this->radius_meters;
    }

    /**
     * Check if a point is within a polygon zone using ray-casting algorithm.
     */
    protected function containsPointPolygon(float $lat, float $lng): bool
    {
        $coordinates = $this->polygon_coordinates;

        if (!$coordinates || count($coordinates) < 3) {
            return false;
        }

        $inside = false;
        $n = count($coordinates);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $coordinates[$i]['lat'] ?? $coordinates[$i][0];
            $yi = $coordinates[$i]['lng'] ?? $coordinates[$i][1];
            $xj = $coordinates[$j]['lat'] ?? $coordinates[$j][0];
            $yj = $coordinates[$j]['lng'] ?? $coordinates[$j][1];

            if ((($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Get the bounding box of this zone.
     */
    public function getBoundingBox(): array
    {
        if ($this->isCircle()) {
            $lat = (float) $this->center_lat;
            $lng = (float) $this->center_lng;
            $radius = $this->radius_meters;

            // Approximate degrees per meter at this latitude
            $latDelta = $radius / 111320;
            $lngDelta = $radius / (111320 * cos(deg2rad($lat)));

            return [
                'north' => $lat + $latDelta,
                'south' => $lat - $latDelta,
                'east' => $lng + $lngDelta,
                'west' => $lng - $lngDelta,
            ];
        }

        $coordinates = $this->polygon_coordinates;
        if (!$coordinates) {
            return [];
        }

        $lats = array_map(fn($c) => $c['lat'] ?? $c[0], $coordinates);
        $lngs = array_map(fn($c) => $c['lng'] ?? $c[1], $coordinates);

        return [
            'north' => max($lats),
            'south' => min($lats),
            'east' => max($lngs),
            'west' => min($lngs),
        ];
    }

    /**
     * Convert zone to GeoJSON feature.
     */
    public function toGeoJson(): array
    {
        $geometry = $this->isCircle()
            ? [
                'type' => 'Point',
                'coordinates' => [(float) $this->center_lng, (float) $this->center_lat],
            ]
            : [
                'type' => 'Polygon',
                'coordinates' => [$this->getPolygonCoordinatesAsGeoJson()],
            ];

        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $this->id,
                'name' => $this->name,
                'zone_type' => $this->zone_type,
                'radius_meters' => $this->radius_meters,
                'fill_color' => $this->fill_color,
                'fill_opacity' => (float) $this->fill_opacity,
                'stroke_color' => $this->stroke_color,
                'stroke_width' => $this->stroke_width,
                'status' => $this->status,
            ],
            'geometry' => $geometry,
        ];
    }

    protected function getPolygonCoordinatesAsGeoJson(): array
    {
        if (!$this->polygon_coordinates) {
            return [];
        }

        return array_map(function ($coord) {
            $lng = $coord['lng'] ?? $coord[1];
            $lat = $coord['lat'] ?? $coord[0];
            return [$lng, $lat];
        }, $this->polygon_coordinates);
    }
}
