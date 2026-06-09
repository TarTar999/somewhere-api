<?php

namespace App\Services;

class GeoService
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Calculate distance between two points using Haversine formula.
     * Returns distance in meters.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check if a point is within a circle.
     */
    public function isPointInCircle(
        float $pointLat,
        float $pointLng,
        float $centerLat,
        float $centerLng,
        float $radiusMeters
    ): bool {
        $distance = $this->calculateDistance($pointLat, $pointLng, $centerLat, $centerLng);
        return $distance <= $radiusMeters;
    }

    /**
     * Check if a point is within a polygon using ray-casting algorithm.
     */
    public function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $this->getCoordValue($polygon[$i], 'lat');
            $yi = $this->getCoordValue($polygon[$i], 'lng');
            $xj = $this->getCoordValue($polygon[$j], 'lat');
            $yj = $this->getCoordValue($polygon[$j], 'lng');

            if ((($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Calculate the center point of a polygon.
     */
    public function calculatePolygonCenter(array $polygon): array
    {
        if (empty($polygon)) {
            return ['lat' => 0, 'lng' => 0];
        }

        $latSum = 0;
        $lngSum = 0;
        $count = count($polygon);

        foreach ($polygon as $coord) {
            $latSum += $this->getCoordValue($coord, 'lat');
            $lngSum += $this->getCoordValue($coord, 'lng');
        }

        return [
            'lat' => $latSum / $count,
            'lng' => $lngSum / $count,
        ];
    }

    /**
     * Calculate the area of a polygon in square meters using Shoelace formula.
     */
    public function calculatePolygonArea(array $polygon): float
    {
        if (count($polygon) < 3) {
            return 0;
        }

        $n = count($polygon);
        $area = 0;

        // Convert to meters using center point as reference
        $center = $this->calculatePolygonCenter($polygon);
        $points = [];

        foreach ($polygon as $coord) {
            $lat = $this->getCoordValue($coord, 'lat');
            $lng = $this->getCoordValue($coord, 'lng');

            // Convert to approximate meters from center
            $x = ($lng - $center['lng']) * cos(deg2rad($center['lat'])) * 111320;
            $y = ($lat - $center['lat']) * 110540;

            $points[] = ['x' => $x, 'y' => $y];
        }

        // Shoelace formula
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $area += $points[$i]['x'] * $points[$j]['y'];
            $area -= $points[$j]['x'] * $points[$i]['y'];
        }

        return abs($area) / 2;
    }

    /**
     * Calculate the area of a circle in square meters.
     */
    public function calculateCircleArea(float $radiusMeters): float
    {
        return M_PI * $radiusMeters ** 2;
    }

    /**
     * Get the bounding box of a polygon.
     */
    public function getPolygonBoundingBox(array $polygon): array
    {
        if (empty($polygon)) {
            return [];
        }

        $lats = array_map(fn($c) => $this->getCoordValue($c, 'lat'), $polygon);
        $lngs = array_map(fn($c) => $this->getCoordValue($c, 'lng'), $polygon);

        return [
            'north' => max($lats),
            'south' => min($lats),
            'east' => max($lngs),
            'west' => min($lngs),
        ];
    }

    /**
     * Get the bounding box of a circle.
     */
    public function getCircleBoundingBox(float $centerLat, float $centerLng, float $radiusMeters): array
    {
        // Approximate degrees per meter at this latitude
        $latDelta = $radiusMeters / 111320;
        $lngDelta = $radiusMeters / (111320 * cos(deg2rad($centerLat)));

        return [
            'north' => $centerLat + $latDelta,
            'south' => $centerLat - $latDelta,
            'east' => $centerLng + $lngDelta,
            'west' => $centerLng - $lngDelta,
        ];
    }

    /**
     * Check if two bounding boxes intersect.
     */
    public function doBoundingBoxesIntersect(array $box1, array $box2): bool
    {
        return !(
            $box1['east'] < $box2['west'] ||
            $box1['west'] > $box2['east'] ||
            $box1['north'] < $box2['south'] ||
            $box1['south'] > $box2['north']
        );
    }

    /**
     * Calculate bearing from point A to point B.
     * Returns bearing in degrees (0-360).
     */
    public function calculateBearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLng = deg2rad($lng2 - $lng1);

        $x = cos($lat2Rad) * sin($deltaLng);
        $y = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLng);

        $bearing = atan2($x, $y);
        $bearing = rad2deg($bearing);

        return fmod($bearing + 360, 360);
    }

    /**
     * Calculate a destination point given a start point, bearing, and distance.
     */
    public function calculateDestinationPoint(
        float $lat,
        float $lng,
        float $bearingDegrees,
        float $distanceMeters
    ): array {
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $bearingRad = deg2rad($bearingDegrees);
        $angularDistance = $distanceMeters / self::EARTH_RADIUS_METERS;

        $destLatRad = asin(
            sin($latRad) * cos($angularDistance) +
            cos($latRad) * sin($angularDistance) * cos($bearingRad)
        );

        $destLngRad = $lngRad + atan2(
            sin($bearingRad) * sin($angularDistance) * cos($latRad),
            cos($angularDistance) - sin($latRad) * sin($destLatRad)
        );

        return [
            'lat' => rad2deg($destLatRad),
            'lng' => rad2deg($destLngRad),
        ];
    }

    /**
     * Simplify a polygon using Douglas-Peucker algorithm.
     */
    public function simplifyPolygon(array $polygon, float $toleranceMeters = 10): array
    {
        if (count($polygon) < 3) {
            return $polygon;
        }

        return $this->douglasPeucker($polygon, $toleranceMeters);
    }

    private function douglasPeucker(array $points, float $tolerance): array
    {
        if (count($points) < 3) {
            return $points;
        }

        $maxDistance = 0;
        $maxIndex = 0;
        $first = $points[0];
        $last = $points[count($points) - 1];

        for ($i = 1; $i < count($points) - 1; $i++) {
            $distance = $this->perpendicularDistance($points[$i], $first, $last);
            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxIndex = $i;
            }
        }

        if ($maxDistance > $tolerance) {
            $leftPoints = $this->douglasPeucker(array_slice($points, 0, $maxIndex + 1), $tolerance);
            $rightPoints = $this->douglasPeucker(array_slice($points, $maxIndex), $tolerance);

            return array_merge(array_slice($leftPoints, 0, -1), $rightPoints);
        }

        return [$first, $last];
    }

    private function perpendicularDistance(array $point, array $lineStart, array $lineEnd): float
    {
        $lat = $this->getCoordValue($point, 'lat');
        $lng = $this->getCoordValue($point, 'lng');
        $lat1 = $this->getCoordValue($lineStart, 'lat');
        $lng1 = $this->getCoordValue($lineStart, 'lng');
        $lat2 = $this->getCoordValue($lineEnd, 'lat');
        $lng2 = $this->getCoordValue($lineEnd, 'lng');

        $A = $lat - $lat1;
        $B = $lng - $lng1;
        $C = $lat2 - $lat1;
        $D = $lng2 - $lng1;

        $dot = $A * $C + $B * $D;
        $lenSq = $C * $C + $D * $D;
        $param = $lenSq !== 0 ? $dot / $lenSq : -1;

        if ($param < 0) {
            $nearestLat = $lat1;
            $nearestLng = $lng1;
        } elseif ($param > 1) {
            $nearestLat = $lat2;
            $nearestLng = $lng2;
        } else {
            $nearestLat = $lat1 + $param * $C;
            $nearestLng = $lng1 + $param * $D;
        }

        return $this->calculateDistance($lat, $lng, $nearestLat, $nearestLng);
    }

    /**
     * Extract coordinate value from various formats.
     */
    private function getCoordValue(array $coord, string $key): float
    {
        if (isset($coord[$key])) {
            return (float) $coord[$key];
        }

        // Support [lat, lng] or [lng, lat] formats
        if ($key === 'lat') {
            return (float) ($coord[0] ?? 0);
        }

        return (float) ($coord[1] ?? 0);
    }
}
