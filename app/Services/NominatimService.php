<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NominatimService
{
    protected string $baseUrl = 'https://nominatim.openstreetmap.org';
    protected string $email;

    public function __construct()
    {
        $this->email = config('services.nominatim.email', config('mail.from.address', 'contact@example.com'));
    }

    /**
     * Reverse geocoding with street geometry
     * Returns street/way information for a given coordinate
     */
    public function reverseGeocode(float $lat, float $lon, int $zoom = 17): ?array
    {
        $cacheKey = "nominatim_reverse_{$lat}_{$lon}_{$zoom}";

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $zoom) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'User-Agent' => config('app.name', 'SomewhereApp') . '/1.0',
                    ])
                    ->get("{$this->baseUrl}/reverse", [
                        'format' => 'jsonv2',
                        'lat' => $lat,
                        'lon' => $lon,
                        'zoom' => $zoom,
                        'email' => $this->email,
                        'polygon_geojson' => 1,
                        'addressdetails' => 1,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Nominatim reverse geocode failed', [
                    'status' => $response->status(),
                    'lat' => $lat,
                    'lon' => $lon,
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Nominatim reverse geocode error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Check if the response is a valid street/way
     */
    public function isStreet(array $data): bool
    {
        $validTypes = ['way', 'relation'];
        $validCategories = ['highway'];
        $validAddressTypes = ['road', 'street', 'path', 'footway', 'residential'];

        return in_array($data['osm_type'] ?? '', $validTypes)
            && (
                in_array($data['category'] ?? '', $validCategories)
                || in_array($data['addresstype'] ?? '', $validAddressTypes)
            );
    }

    /**
     * Extract street data from Nominatim response
     */
    public function extractStreetData(array $data): ?array
    {
        if (!$this->isStreet($data)) {
            return null;
        }

        return [
            'osm_id' => $data['osm_id'],
            'osm_type' => $data['osm_type'],
            'display_name' => $data['display_name'],
            'address' => $data['address'] ?? [],
            'geojson' => $data['geojson'] ?? null,
            'boundingbox' => $data['boundingbox'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lon' => $data['lon'] ?? null,
        ];
    }
}
