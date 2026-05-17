<?php

namespace App\Services;

use App\Models\Street;

class StreetService
{
    /**
     * Calculate house number based on position relative to street
     *
     * @param Street $street The street
     * @param float $lat House latitude
     * @param float $lon House longitude
     * @return array Contains: street_number, distance_on_street, street_side
     */
    public function calculateHouseNumber(Street $street, float $lat, float $lon): array
    {
        $coordinates = $street->structure['coordinates'] ?? [];

        if (count($coordinates) < 2) {
            return [
                'street_number' => 1,
                'distance_on_street' => 0,
                'street_side' => 'right',
            ];
        }

        // Find the closest segment and projection point
        $projection = $this->findProjectionOnStreet($coordinates, $lat, $lon);

        // Calculate distance from street start to projection point
        $distanceFromStart = $this->calculateDistanceAlongStreet(
            $coordinates,
            $projection['segment_index'],
            $projection['projection_point']
        );

        // Determine which side of the street (left or right)
        $side = $this->determineStreetSide(
            $coordinates,
            $projection['segment_index'],
            $lat,
            $lon
        );

        // Calculate house number: distance in meters, rounded
        // Even numbers on left, odd numbers on right
        $baseNumber = max(1, (int) round($distanceFromStart));

        // Adjust to get even for left, odd for right
        if ($side === 'left') {
            // Even number
            $streetNumber = $baseNumber % 2 === 0 ? $baseNumber : $baseNumber + 1;
        } else {
            // Odd number
            $streetNumber = $baseNumber % 2 === 1 ? $baseNumber : $baseNumber + 1;
        }

        return [
            'street_number' => $streetNumber,
            'distance_on_street' => round($distanceFromStart, 2),
            'street_side' => $side,
        ];
    }

    /**
     * Find the orthogonal projection of a point onto the street polyline
     */
    protected function findProjectionOnStreet(array $coordinates, float $lat, float $lon): array
    {
        $minDistance = PHP_FLOAT_MAX;
        $bestProjection = null;
        $bestSegmentIndex = 0;

        for ($i = 0; $i < count($coordinates) - 1; $i++) {
            // Coordinates are [lon, lat] in GeoJSON
            $p1 = ['lat' => $coordinates[$i][1], 'lon' => $coordinates[$i][0]];
            $p2 = ['lat' => $coordinates[$i + 1][1], 'lon' => $coordinates[$i + 1][0]];

            $projection = $this->projectPointOnSegment($lat, $lon, $p1, $p2);
            $distance = $this->haversineDistance($lat, $lon, $projection['lat'], $projection['lon']);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestProjection = $projection;
                $bestSegmentIndex = $i;
            }
        }

        return [
            'projection_point' => $bestProjection,
            'segment_index' => $bestSegmentIndex,
            'distance_to_street' => $minDistance,
        ];
    }

    /**
     * Project a point onto a line segment
     */
    protected function projectPointOnSegment(float $lat, float $lon, array $p1, array $p2): array
    {
        // Convert to simple Cartesian for projection calculation
        // (approximation valid for small distances)
        $dx = $p2['lon'] - $p1['lon'];
        $dy = $p2['lat'] - $p1['lat'];

        if ($dx == 0 && $dy == 0) {
            return $p1;
        }

        // Calculate projection parameter t
        $t = (($lon - $p1['lon']) * $dx + ($lat - $p1['lat']) * $dy) / ($dx * $dx + $dy * $dy);

        // Clamp t to [0, 1] to stay on segment
        $t = max(0, min(1, $t));

        return [
            'lat' => $p1['lat'] + $t * $dy,
            'lon' => $p1['lon'] + $t * $dx,
            't' => $t,
        ];
    }

    /**
     * Calculate distance along street from start to a projection point
     */
    protected function calculateDistanceAlongStreet(array $coordinates, int $segmentIndex, array $projectionPoint): float
    {
        $totalDistance = 0;

        // Sum distances of complete segments before the projection segment
        for ($i = 0; $i < $segmentIndex; $i++) {
            $totalDistance += $this->haversineDistance(
                $coordinates[$i][1],
                $coordinates[$i][0],
                $coordinates[$i + 1][1],
                $coordinates[$i + 1][0]
            );
        }

        // Add partial distance on the projection segment
        $totalDistance += $this->haversineDistance(
            $coordinates[$segmentIndex][1],
            $coordinates[$segmentIndex][0],
            $projectionPoint['lat'],
            $projectionPoint['lon']
        );

        return $totalDistance;
    }

    /**
     * Determine which side of the street the house is on
     * Uses cross product to determine left/right based on street direction
     * Left = even numbers, Right = odd numbers
     */
    protected function determineStreetSide(array $coordinates, int $segmentIndex, float $lat, float $lon): string
    {
        // Get the segment direction
        $p1 = $coordinates[$segmentIndex];
        $p2 = $coordinates[$segmentIndex + 1];

        // Vector from p1 to p2 (street direction)
        $streetVecLon = $p2[0] - $p1[0];
        $streetVecLat = $p2[1] - $p1[1];

        // Vector from p1 to house
        $houseVecLon = $lon - $p1[0];
        $houseVecLat = $lat - $p1[1];

        // 2D cross product: street x house
        // Positive = house is on the left side (when looking in street direction)
        // Negative = house is on the right side
        $crossProduct = $streetVecLon * $houseVecLat - $streetVecLat * $houseVecLon;

        // In the coordinate system (street direction, up/earth center, perpendicular)
        // Left side gets even numbers, right side gets odd numbers
        return $crossProduct >= 0 ? 'left' : 'right';
    }

    /**
     * Calculate distance between two points using Haversine formula
     * Returns distance in meters
     */
    protected function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Generate SW address from street code and house number
     * Format: @{numéro_maison} Rue {code_rue}
     * Example: "@156 Rue 3.0002"
     */
    public function generateSwAddress(Street $street, int $streetNumber): string
    {
        return "@{$streetNumber} Rue {$street->code}";
    }

    /**
     * Calculate GPS coordinates from street and distance (house number)
     * This is the reverse of calculateHouseNumber
     *
     * @param Street $street The street
     * @param int $streetNumber The house number (distance in meters from start)
     * @return array|null Contains: latitude, longitude, street_side
     */
    public function calculateCoordinatesFromNumber(Street $street, int $streetNumber): ?array
    {
        $coordinates = $street->structure['coordinates'] ?? [];

        if (count($coordinates) < 2) {
            return null;
        }

        // Target distance is approximately the street number (in meters)
        $targetDistance = $streetNumber;

        // Determine side based on even/odd
        $side = ($streetNumber % 2 === 0) ? 'left' : 'right';

        // Walk along the street until we reach the target distance
        $accumulatedDistance = 0;

        for ($i = 0; $i < count($coordinates) - 1; $i++) {
            $p1 = ['lat' => $coordinates[$i][1], 'lon' => $coordinates[$i][0]];
            $p2 = ['lat' => $coordinates[$i + 1][1], 'lon' => $coordinates[$i + 1][0]];

            $segmentLength = $this->haversineDistance($p1['lat'], $p1['lon'], $p2['lat'], $p2['lon']);

            if ($accumulatedDistance + $segmentLength >= $targetDistance) {
                // The point is on this segment
                $remainingDistance = $targetDistance - $accumulatedDistance;
                $ratio = $segmentLength > 0 ? $remainingDistance / $segmentLength : 0;

                // Interpolate position on segment
                $pointLat = $p1['lat'] + $ratio * ($p2['lat'] - $p1['lat']);
                $pointLon = $p1['lon'] + $ratio * ($p2['lon'] - $p1['lon']);

                // Offset perpendicular to street based on side (approximately 5 meters)
                $offset = $this->calculatePerpendicularOffset($p1, $p2, $side, 5);

                return [
                    'latitude' => round($pointLat + $offset['lat'], 8),
                    'longitude' => round($pointLon + $offset['lon'], 8),
                    'streetSide' => $side,
                    'distanceOnStreet' => $targetDistance,
                    'segmentIndex' => $i,
                ];
            }

            $accumulatedDistance += $segmentLength;
        }

        // If target distance exceeds street length, return the end point
        $lastCoord = end($coordinates);
        return [
            'latitude' => round($lastCoord[1], 8),
            'longitude' => round($lastCoord[0], 8),
            'streetSide' => $side,
            'distanceOnStreet' => $accumulatedDistance,
            'segmentIndex' => count($coordinates) - 2,
        ];
    }

    /**
     * Calculate a perpendicular offset from the street line
     *
     * @param array $p1 Start point of segment ['lat', 'lon']
     * @param array $p2 End point of segment ['lat', 'lon']
     * @param string $side 'left' or 'right'
     * @param float $meters Offset distance in meters
     * @return array ['lat' => offset, 'lon' => offset]
     */
    protected function calculatePerpendicularOffset(array $p1, array $p2, string $side, float $meters): array
    {
        // Calculate direction vector
        $dx = $p2['lon'] - $p1['lon'];
        $dy = $p2['lat'] - $p1['lat'];

        // Normalize
        $length = sqrt($dx * $dx + $dy * $dy);
        if ($length == 0) {
            return ['lat' => 0, 'lon' => 0];
        }

        $dx /= $length;
        $dy /= $length;

        // Perpendicular vector (rotate 90 degrees)
        // Left side: rotate counter-clockwise
        // Right side: rotate clockwise
        if ($side === 'left') {
            $perpLon = -$dy;
            $perpLat = $dx;
        } else {
            $perpLon = $dy;
            $perpLat = -$dx;
        }

        // Convert meters to approximate degrees (at equator: 1 degree ≈ 111,320 meters)
        // This is a rough approximation, good enough for small offsets
        $metersPerDegreeLat = 111320;
        $metersPerDegreeLon = 111320 * cos(deg2rad(($p1['lat'] + $p2['lat']) / 2));

        return [
            'lat' => ($perpLat * $meters) / $metersPerDegreeLat,
            'lon' => ($perpLon * $meters) / $metersPerDegreeLon,
        ];
    }

    /**
     * Parse a SomeWhere address query and extract components
     * Formats supported:
     * - "@156 Rue 3.0002"
     * - "156 Rue 3.0002"
     * - "@156 3.0002"
     *
     * @param string $query The search query
     * @return array|null ['streetNumber' => int, 'streetCode' => string] or null
     */
    public function parseSwAddressQuery(string $query): ?array
    {
        $query = trim($query);

        // Remove @ prefix if present
        $query = ltrim($query, '@');

        // Split by spaces
        $parts = preg_split('/\s+/', $query);

        if (count($parts) < 2) {
            return null;
        }

        // First part should be the street number
        $streetNumber = null;
        if (is_numeric($parts[0])) {
            $streetNumber = (int) $parts[0];
        } else {
            return null;
        }

        // Find the street code (looks like X.XXXX)
        $streetCode = null;
        foreach ($parts as $index => $part) {
            // Skip "Rue", "Ave", etc.
            if (in_array(strtolower($part), ['rue', 'ave', 'avenue', 'blvd', 'boulevard'])) {
                continue;
            }
            // Check if it looks like a street code (number.hexcode)
            if (preg_match('/^\d+\.[A-F0-9]+$/i', $part)) {
                $streetCode = strtoupper($part);
                break;
            }
        }

        if (!$streetCode) {
            return null;
        }

        // Normalize the street code to padded format
        $streetCode = Street::formatCodeToPadded($streetCode);

        return [
            'streetNumber' => $streetNumber,
            'streetCode' => $streetCode,
        ];
    }
}
