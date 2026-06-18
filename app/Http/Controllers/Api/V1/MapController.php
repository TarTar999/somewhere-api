<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    /**
     * Get heatmap data for address density visualization.
     */
    public function heatmap(Request $request): JsonResponse
    {
        $request->validate([
            'precision' => 'nullable|integer|min:2|max:6',
            'bounds' => 'nullable|array',
            'bounds.north' => 'required_with:bounds|numeric',
            'bounds.south' => 'required_with:bounds|numeric',
            'bounds.east' => 'required_with:bounds|numeric',
            'bounds.west' => 'required_with:bounds|numeric',
            'zone_id' => 'nullable|integer|exists:zones,id',
        ]);

        $precision = $request->input('precision', 3);
        $bounds = $request->input('bounds');
        $zoneId = $request->input('zone_id');

        $query = Address::query()
            ->selectRaw(
                "ROUND(latitude, ?) as lat, ROUND(longitude, ?) as lng, COUNT(*) as count",
                [$precision, $precision]
            );

        // Filter by bounds if provided
        if ($bounds) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        // Filter by zone if provided
        if ($zoneId) {
            $zone = Zone::findOrFail($zoneId);
            $query->whereRaw($this->getZoneContainsQuery($zone));
        }

        $data = $query->groupBy('lat', 'lng')->get();

        // Calculate max count for normalization
        $maxCount = $data->max('count') ?: 1;

        $heatmapPoints = $data->map(function ($point) use ($maxCount) {
            return [
                'lat' => (float) $point->lat,
                'lng' => (float) $point->lng,
                'intensity' => min($point->count / $maxCount, 1),
                'count' => (int) $point->count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $heatmapPoints,
            'meta' => [
                'total_points' => $heatmapPoints->count(),
                'total_addresses' => $data->sum('count'),
                'max_density' => $maxCount,
            ],
        ]);
    }

    /**
     * Get cluster data for marker clustering.
     */
    public function clusters(Request $request): JsonResponse
    {
        $request->validate([
            'zoom' => 'required|integer|min:1|max:20',
            'bounds' => 'required|array',
            'bounds.north' => 'required|numeric',
            'bounds.south' => 'required|numeric',
            'bounds.east' => 'required|numeric',
            'bounds.west' => 'required|numeric',
        ]);

        $zoom = $request->input('zoom');
        $bounds = $request->input('bounds');

        // Calculate grid size based on zoom level
        $gridSize = $this->getGridSizeForZoom($zoom);

        $addresses = Address::query()
            ->whereBetween('latitude', [$bounds['south'], $bounds['north']])
            ->whereBetween('longitude', [$bounds['west'], $bounds['east']])
            ->select(['id', 'latitude', 'longitude', 'sw_address', 'verification_status'])
            ->get();

        // Group addresses into clusters
        $clusters = $this->clusterAddresses($addresses, $gridSize);

        return response()->json([
            'success' => true,
            'data' => $clusters,
            'meta' => [
                'zoom' => $zoom,
                'total_addresses' => $addresses->count(),
                'total_clusters' => count($clusters),
            ],
        ]);
    }

    /**
     * Get zone statistics.
     */
    public function zoneStats(Request $request, Zone $zone): JsonResponse
    {
        $containsQuery = $this->getZoneContainsQuery($zone);

        $addresses = Address::whereRaw($containsQuery)->get();

        $stats = [
            'zone_id' => $zone->id,
            'zone_name' => $zone->name,
            'total_addresses' => $addresses->count(),
            'verified_addresses' => $addresses->where('verification_status', 'approved')->count(),
            'pending_addresses' => $addresses->where('verification_status', 'pending')->count(),
            'rejected_addresses' => $addresses->where('verification_status', 'rejected')->count(),
            'coverage_area' => $this->calculateZoneArea($zone),
            'density' => $addresses->count() > 0 ? $addresses->count() / max($this->calculateZoneArea($zone), 0.01) : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get addresses within bounds.
     */
    public function addressesInBounds(Request $request): JsonResponse
    {
        $request->validate([
            'bounds' => 'required|array',
            'bounds.north' => 'required|numeric',
            'bounds.south' => 'required|numeric',
            'bounds.east' => 'required|numeric',
            'bounds.west' => 'required|numeric',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $bounds = $request->input('bounds');
        $limit = $request->input('limit', 100);

        $addresses = Address::query()
            ->whereBetween('latitude', [$bounds['south'], $bounds['north']])
            ->whereBetween('longitude', [$bounds['west'], $bounds['east']])
            ->with('street:id,display_name,code')
            ->select(['id', 'latitude', 'longitude', 'sw_address', 'quarter', 'verification_status', 'street_id'])
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses->map(fn($addr) => [
                'id' => $addr->id,
                'latitude' => $addr->latitude,
                'longitude' => $addr->longitude,
                'sw_address' => $addr->sw_address,
                'quarter' => $addr->quarter,
                'verification_status' => $addr->verification_status,
                'street_name' => $addr->street?->display_name,
            ]),
            'meta' => [
                'total' => $addresses->count(),
                'limited' => $addresses->count() >= $limit,
            ],
        ]);
    }

    /**
     * Search addresses and zones.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'types' => 'nullable|array',
            'types.*' => 'in:address,zone,collection',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('query');
        $types = $request->input('types', ['address', 'zone']);
        $limit = $request->input('limit', 10);

        $results = collect();

        // Search addresses
        if (in_array('address', $types)) {
            $addresses = Address::query()
                ->where('sw_address', 'like', "%{$query}%")
                ->orWhere('quarter', 'like', "%{$query}%")
                ->orWhereHas('street', function ($q) use ($query) {
                    $q->where('display_name', 'like', "%{$query}%");
                })
                ->select(['id', 'sw_address', 'quarter', 'latitude', 'longitude'])
                ->limit($limit)
                ->get()
                ->map(fn($addr) => [
                    'id' => (string) $addr->id,
                    'title' => $addr->sw_address,
                    'subtitle' => $addr->quarter,
                    'coordinates' => [$addr->latitude, $addr->longitude],
                    'type' => 'address',
                ]);

            $results = $results->concat($addresses);
        }

        // Search zones
        if (in_array('zone', $types)) {
            $zones = Zone::query()
                ->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->select(['id', 'name', 'description', 'center', 'type'])
                ->limit($limit)
                ->get()
                ->map(fn($zone) => [
                    'id' => 'zone_' . $zone->id,
                    'title' => $zone->name,
                    'subtitle' => $zone->description ?? ucfirst($zone->type),
                    'coordinates' => $zone->center ? [$zone->center['lat'], $zone->center['lng']] : null,
                    'type' => 'zone',
                ]);

            $results = $results->concat($zones);
        }

        return response()->json([
            'success' => true,
            'data' => $results->take($limit)->values(),
        ]);
    }

    /**
     * Get grid size based on zoom level.
     */
    private function getGridSizeForZoom(int $zoom): float
    {
        // Larger grid at lower zoom, smaller at higher zoom
        return match (true) {
            $zoom >= 18 => 0.0001,
            $zoom >= 16 => 0.0005,
            $zoom >= 14 => 0.001,
            $zoom >= 12 => 0.005,
            $zoom >= 10 => 0.01,
            $zoom >= 8 => 0.05,
            default => 0.1,
        };
    }

    /**
     * Cluster addresses based on grid size.
     */
    private function clusterAddresses($addresses, float $gridSize): array
    {
        $clusters = [];

        foreach ($addresses as $address) {
            $gridLat = floor($address->latitude / $gridSize) * $gridSize;
            $gridLng = floor($address->longitude / $gridSize) * $gridSize;
            $key = "{$gridLat}_{$gridLng}";

            if (!isset($clusters[$key])) {
                $clusters[$key] = [
                    'lat' => $gridLat + ($gridSize / 2),
                    'lng' => $gridLng + ($gridSize / 2),
                    'count' => 0,
                    'addresses' => [],
                ];
            }

            $clusters[$key]['count']++;
            if (count($clusters[$key]['addresses']) < 5) {
                $clusters[$key]['addresses'][] = [
                    'id' => $address->id,
                    'sw_address' => $address->sw_address,
                ];
            }
        }

        return array_values($clusters);
    }

    /**
     * Get SQL query for zone containment check.
     */
    private function getZoneContainsQuery(Zone $zone): string
    {
        if ($zone->type === 'circle' && $zone->center && $zone->radius) {
            $lat = $zone->center['lat'];
            $lng = $zone->center['lng'];
            $radius = $zone->radius;

            // Haversine formula approximation
            return "
                (6371000 * acos(
                    cos(radians({$lat})) * cos(radians(latitude)) * cos(radians(longitude) - radians({$lng})) +
                    sin(radians({$lat})) * sin(radians(latitude))
                )) <= {$radius}
            ";
        }

        if ($zone->type === 'polygon' && $zone->coordinates) {
            // For polygon, we would need PostGIS or similar
            // This is a simplified bounding box check
            $lats = array_column($zone->coordinates, 0);
            $lngs = array_column($zone->coordinates, 1);

            $minLat = min($lats);
            $maxLat = max($lats);
            $minLng = min($lngs);
            $maxLng = max($lngs);

            return "
                latitude BETWEEN {$minLat} AND {$maxLat} AND
                longitude BETWEEN {$minLng} AND {$maxLng}
            ";
        }

        return '1=0';
    }

    /**
     * Calculate zone area in square kilometers.
     */
    private function calculateZoneArea(Zone $zone): float
    {
        if ($zone->type === 'circle' && $zone->radius) {
            // Circle area: πr²
            $radiusKm = $zone->radius / 1000;
            return pi() * pow($radiusKm, 2);
        }

        if ($zone->type === 'polygon' && $zone->coordinates) {
            // Shoelace formula for polygon area
            $coords = $zone->coordinates;
            $n = count($coords);
            $area = 0;

            for ($i = 0; $i < $n; $i++) {
                $j = ($i + 1) % $n;
                $area += $coords[$i][1] * $coords[$j][0];
                $area -= $coords[$j][1] * $coords[$i][0];
            }

            // Convert to km² (approximate)
            return abs($area / 2) * 111 * 111;
        }

        return 0;
    }
}
