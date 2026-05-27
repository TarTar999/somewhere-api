<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Street;
use App\Services\StreetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StreetController extends Controller
{
    public function __construct(
        protected StreetService $streetService
    ) {}

    /**
     * Verify street and calculate SomeWhere address
     *
     * Receives GPS coordinates + Nominatim data,
     * finds or creates the street, calculates house number,
     * and returns the complete SomeWhere address.
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'nominatim' => 'required|array',
            'nominatim.osm_id' => 'required|integer',
            'nominatim.osm_type' => 'nullable|string',
            'nominatim.display_name' => 'required|string',
            'nominatim.address' => 'nullable|array',
            'nominatim.geojson' => 'nullable|array',
            'nominatim.boundingbox' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $nominatimData = $request->nominatim;

        // 1. Find or create street by osm_id
        $street = Street::findOrCreateFromNominatim($nominatimData);

        // 2. Calculate house number based on position
        $houseData = $this->streetService->calculateHouseNumber(
            $street,
            $latitude,
            $longitude
        );

        // 3. Generate SomeWhere address
        $swAddress = $this->streetService->generateSwAddress($street, $houseData['street_number']);

        return $this->success([
            'swAddress' => $swAddress,
            'streetNumber' => $houseData['street_number'],
            'distanceOnStreet' => $houseData['distance_on_street'],
            'streetSide' => $houseData['street_side'],
            'street' => [
                'id' => $street->id,
                'osmId' => $street->osm_id,
                'code' => $street->code,
                'displayName' => $street->display_name,
                'communeName' => $street->commune_name,
                'communeNumber' => $street->commune_number,
            ],
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ], 'Address verified successfully');
    }

    /**
     * Get street by OSM ID
     */
    public function showByOsmId(int $osmId): JsonResponse
    {
        $street = Street::where('osm_id', $osmId)->first();

        if (!$street) {
            return $this->error('Street not found', 404);
        }

        return $this->success($this->formatStreet($street));
    }

    /**
     * Get street by code
     */
    public function showByCode(string $code): JsonResponse
    {
        $street = Street::where('code', $code)->first();

        if (!$street) {
            return $this->error('Street not found', 404);
        }

        return $this->success($this->formatStreet($street));
    }

    /**
     * List streets in a commune
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'commune_number' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $query = Street::query();

        if ($request->has('commune_number')) {
            $query->where('commune_number', $request->commune_number);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $limit = $request->limit ?? 50;
        $streets = $query->orderBy('code')->limit($limit)->get();

        return $this->success([
            'streets' => $streets->map(fn($s) => $this->formatStreet($s))->toArray(),
            'count' => $streets->count(),
        ]);
    }

    /**
     * Calculate address for a position on an existing street
     */
    public function calculateAddress(Request $request, int $streetId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $street = Street::find($streetId);

        if (!$street) {
            return $this->error('Street not found', 404);
        }

        $houseData = $this->streetService->calculateHouseNumber(
            $street,
            $request->latitude,
            $request->longitude
        );

        $swAddress = $this->streetService->generateSwAddress($street, $houseData['street_number']);

        return $this->success([
            'swAddress' => $swAddress,
            'streetNumber' => $houseData['street_number'],
            'distanceOnStreet' => $houseData['distance_on_street'],
            'streetSide' => $houseData['street_side'],
            'street' => $this->formatStreet($street),
        ]);
    }

    /**
     * Format street for response
     */
    protected function formatStreet(Street $street): array
    {
        return [
            'id' => $street->id,
            'osmId' => $street->osm_id,
            'osmType' => $street->osm_type,
            'code' => $street->code,
            'displayName' => $street->display_name,
            'communeName' => $street->commune_name,
            'communeNumber' => $street->commune_number,
            'boundingBox' => $street->bounding_box,
            'startPoint' => $street->start_point,
            'endPoint' => $street->end_point,
            'createdAt' => $street->created_at->toIso8601String(),
        ];
    }
}
