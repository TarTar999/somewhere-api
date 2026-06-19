<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Address\ScanAddressRequest;
use App\Http\Requests\Api\Address\ShareAddressRequest;
use App\Http\Requests\Api\Address\StoreAddressRequest;
use App\Http\Requests\Api\Address\UpdateAddressRequest;
use App\Models\Address;
use App\Models\Domiciliation;
use App\Models\Street;
use App\Services\FileUploadService;
use App\Services\NominatimService;
use App\Services\QrCodeService;
use App\Services\StreetService;
use App\Services\SwAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AddressController extends Controller
{
    public function __construct(
        protected SwAddressService $swAddressService,
        protected QrCodeService $qrCodeService,
        protected FileUploadService $fileUploadService,
        protected NominatimService $nominatimService,
        protected StreetService $streetService
    ) {}

    public function index(): JsonResponse
    {
        $addresses = auth()->user()->addresses()
            ->with(['street', 'itineraryStreet', 'itineraryIntersection'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($this->formatAddresses($addresses));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Get street data from Nominatim or from request
        $street = null;
        $streetNumber = null;
        $distanceOnStreet = null;
        $streetSide = null;
        $swAddress = null;

        // If street data is provided in request, use it
        if ($request->has('streetOsmId') && $request->streetOsmId) {
            $street = Street::where('osm_id', $request->streetOsmId)->first();

            if (!$street && $request->has('streetData')) {
                // Create street from provided data
                $street = Street::findOrCreateFromNominatim($request->streetData);
            }
        } else {
            // Fetch street data from Nominatim using coordinates
            $nominatimData = $this->nominatimService->reverseGeocode(
                $request->latitude,
                $request->longitude
            );

            if ($nominatimData && $this->nominatimService->isStreet($nominatimData)) {
                $street = Street::findOrCreateFromNominatim($nominatimData);
            }
        }

        // Calculate house number based on position relative to street
        if ($street) {
            $houseData = $this->streetService->calculateHouseNumber(
                $street,
                $request->latitude,
                $request->longitude
            );

            $streetNumber = $houseData['street_number'];
            $distanceOnStreet = $houseData['distance_on_street'];
            $streetSide = $houseData['street_side'];

            // Generate SW address from street code and house number
            $swAddress = $this->streetService->generateSwAddress($street, $streetNumber);
        } else {
            // Fallback to legacy SW address generation
            $swAddress = $this->swAddressService->generate(
                $request->latitude,
                $request->longitude
            );
        }

        // Handle video upload
        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $this->fileUploadService->uploadVideo(
                $request->file('video'),
                $user->id
            );
        }

        // Handle signature
        $signaturePath = null;
        if ($request->signature) {
            $signaturePath = $request->signature;
        }

        $address = null;
        $domiciliation = null;

        DB::transaction(function () use (
            $user, $street, $streetNumber, $distanceOnStreet, $streetSide,
            $swAddress, $request, $signaturePath, $videoPath, &$address, &$domiciliation
        ) {
            // Determine honor_declaration value based on conditions
            $isNonHabitation = filter_var($request->isNonHabitation ?? false, FILTER_VALIDATE_BOOLEAN);
            $residentName = $request->residentName;
            $honorDeclaration = filter_var($request->honorDeclaration ?? false, FILTER_VALIDATE_BOOLEAN) || !empty($residentName);

            $address = Address::create([
                'user_id' => $user->id,
                'street_id' => $street?->id,
                'street_number' => $streetNumber,
                'distance_on_street' => $distanceOnStreet,
                'street_side' => $streetSide,
                'sw_address' => $swAddress,
                'display_name' => $request->quarter . ($request->subQuarter ? ' - ' . $request->subQuarter : ''),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'house_type' => $request->houseType,
                'home_status' => $request->homeStatus,
                'quarter' => $request->quarter,
                'sub_quarter' => $request->subQuarter,
                'lieu_dit' => $request->lieuDit,
                'description' => $request->description,
                'official_address' => $request->officialAddress,
                'honor_declaration' => $honorDeclaration,
                'resident_name' => $residentName,
                'is_non_habitation' => $isNonHabitation,
                'signature' => $signaturePath,
                'video_path' => $videoPath,
                'verification_status' => 'pending',
            ]);

            // Generate domiciliation name
            $domiciliationName = $request->domiciliationName ?? $this->generateDomiciliationName($user->id);

            // Check if this should be the primary domiciliation
            $isPrimary = $request->has('isPrimary')
                ? $request->isPrimary
                : !Domiciliation::where('user_id', $user->id)->exists();

            // If setting as primary, unset other primaries
            if ($isPrimary) {
                Domiciliation::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // Create domiciliation for this address
            $domiciliation = Domiciliation::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'invited_by' => null,
                'name' => $domiciliationName,
                'role' => 'owner',
                'status' => 'approved',
                'is_primary' => $isPrimary,
            ]);
        });

        // Load relations for response
        $address->load('street');

        return $this->success([
            'address' => $this->formatAddress($address),
            'domiciliation' => [
                'id' => $domiciliation->id,
                'name' => $domiciliation->name,
                'role' => $domiciliation->role,
                'isPrimary' => $domiciliation->is_primary,
            ],
        ], 'Address created successfully', 201);
    }

    public function show(Address $address): JsonResponse
    {
        // Check if user owns the address or it's shared
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $address->load(['street', 'itineraryStreet', 'itineraryIntersection']);

        return $this->success($this->formatAddress($address));
    }

    public function showBySwAddress(string $swAddress): JsonResponse
    {
        $address = Address::with('street')
            ->where('sw_address', urldecode($swAddress))
            ->first();

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        return $this->success($this->formatAddress($address));
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $data = [];

        if ($request->has('displayName')) {
            $data['display_name'] = $request->displayName;
        }
        if ($request->has('houseType')) {
            $data['house_type'] = $request->houseType;
        }
        if ($request->has('homeStatus')) {
            $data['home_status'] = $request->homeStatus;
        }
        if ($request->has('quarter')) {
            $data['quarter'] = $request->quarter;
        }
        if ($request->has('subQuarter')) {
            $data['sub_quarter'] = $request->subQuarter;
        }
        if ($request->has('lieuDit')) {
            $data['lieu_dit'] = $request->lieuDit;
        }
        if ($request->has('description')) {
            $data['description'] = $request->description;
        }
        if ($request->has('residentName')) {
            $data['resident_name'] = $request->residentName;
        }
        if ($request->has('isNonHabitation')) {
            $data['is_non_habitation'] = filter_var($request->isNonHabitation, FILTER_VALIDATE_BOOLEAN);
        }

        $address->update($data);

        return $this->success($this->formatAddress($address), 'Address updated successfully');
    }

    public function destroy(Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $address->delete();

        return $this->noContent();
    }

    public function share(ShareAddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // Send email with address details
        // In production, you would use a proper mail notification
        // Mail::to($request->recipientEmail)->send(new AddressSharedMail($address));

        return $this->success(null, 'Address shared successfully');
    }

    public function qrCode(Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $qrCodeData = $this->qrCodeService->generateForAddress($address);

        return $this->success([
            'qrCodeUrl' => $qrCodeData,
        ]);
    }

    public function scan(ScanAddressRequest $request): JsonResponse
    {
        $parsedData = $this->qrCodeService->parseQrData($request->qrData);

        if (!$parsedData) {
            return $this->error('Invalid QR code data', 400);
        }

        $address = Address::where('sw_address', $parsedData['sw_address'])->first();

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        return $this->success($this->formatAddress($address));
    }

    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:50',
        ]);

        $radius = $request->radius ?? 10; // Default 10km

        $addresses = Address::nearby(
            $request->latitude,
            $request->longitude,
            $radius
        )->verified()->limit(50)->get();

        return $this->success($this->formatAddresses($addresses));
    }

    /**
     * Search addresses by query
     * Supports formats:
     * - SomeWhere: @370 Rue 3.2, @123 Rue 5.A
     * - Normal: 645 Rue de la Joie, 123 Avenue Kennedy
     *
     * If a SomeWhere address format is used and the street exists but no
     * address is registered, calculates and returns the GPS coordinates.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = trim($request->q);
        $limit = $request->limit ?? 20;

        // Parse the query to determine format
        $searchResult = $this->parseAndSearch($query, $limit);

        $response = [
            'query' => $query,
            'format' => $searchResult['format'],
            'parsed' => $searchResult['parsed'],
            'results' => $this->formatAddresses($searchResult['addresses']),
            'count' => $searchResult['addresses']->count(),
        ];

        // If we have a calculated address (street known but no registered address)
        if (isset($searchResult['calculated'])) {
            $response['calculated'] = $searchResult['calculated'];
        }

        return $this->success($response);
    }

    /**
     * Parse query and search accordingly
     */
    protected function parseAndSearch(string $query, int $limit): array
    {
        // Check if SomeWhere format (starts with @)
        if (str_starts_with($query, '@')) {
            return $this->searchSwFormat($query, $limit);
        }

        // Check if it looks like a street number + street name
        if (preg_match('/^(\d+)\s+(.+)$/i', $query, $matches)) {
            return $this->searchStreetFormat($matches[1], $matches[2], $limit);
        }

        // General search
        return $this->searchGeneral($query, $limit);
    }

    /**
     * Search by SomeWhere format (@370 Rue 3.2)
     * If no registered address found but street exists, calculates GPS coordinates
     */
    protected function searchSwFormat(string $query, int $limit): array
    {
        // Parse the query using StreetService
        $parsed = $this->streetService->parseSwAddressQuery($query);

        // Fallback parsing if service returns null
        if (!$parsed) {
            $swAddress = ltrim($query, '@');
            if (preg_match('/^(\d+)\s+(?:Rue|Ave|Avenue|Blvd|Boulevard)?\s*(\d+\.[A-Z0-9]+)$/i', $swAddress, $matches)) {
                $parsed = [
                    'streetNumber' => (int) $matches[1],
                    'streetCode' => Street::formatCodeToPadded($matches[2]),
                ];
            }
        }

        // Search by sw_address (exact or partial match)
        $swAddress = ltrim($query, '@');
        $addresses = Address::with('street')
            ->where(function ($q) use ($swAddress, $query) {
                $q->where('sw_address', 'LIKE', "%{$swAddress}%")
                  ->orWhere('sw_address', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        // If no results and we have parsed data, search by street code
        if ($addresses->isEmpty() && !empty($parsed['streetCode'])) {
            // Also search with padded code format
            $addresses = Address::with('street')
                ->whereHas('street', function ($q) use ($parsed) {
                    $q->where('code', $parsed['streetCode'])
                      ->orWhere('code', 'LIKE', '%.' . ltrim(explode('.', $parsed['streetCode'])[1] ?? '', '0') . '%');
                })
                ->where(function ($q) use ($parsed) {
                    if (!empty($parsed['streetNumber'])) {
                        $q->where('street_number', $parsed['streetNumber']);
                    }
                })
                ->limit($limit)
                ->get();
        }

        $result = [
            'format' => 'somewhere',
            'parsed' => $parsed ?? [],
            'addresses' => $addresses,
        ];

        // If no registered addresses found but we have valid parsed data,
        // try to find the street and calculate GPS coordinates
        if ($addresses->isEmpty() && $parsed && !empty($parsed['streetCode']) && !empty($parsed['streetNumber'])) {
            $calculatedAddress = $this->calculateAddressFromParsed($parsed);
            if ($calculatedAddress) {
                $result['calculated'] = $calculatedAddress;
            }
        }

        return $result;
    }

    /**
     * Calculate address details from parsed SW address components
     * Returns null if street not found
     */
    protected function calculateAddressFromParsed(array $parsed): ?array
    {
        $streetCode = $parsed['streetCode'];
        $streetNumber = $parsed['streetNumber'];

        // Find the street by code (try both formats)
        $street = Street::where('code', $streetCode)->first();

        // Try without leading zeros if not found
        if (!$street && str_contains($streetCode, '.')) {
            $parts = explode('.', $streetCode);
            $unpadded = $parts[0] . '.' . ltrim($parts[1], '0');
            if ($unpadded !== $streetCode) {
                $street = Street::where('code', $unpadded)->first();
            }
        }

        if (!$street) {
            return null;
        }

        // Calculate GPS coordinates from street and house number
        $coordinates = $this->streetService->calculateCoordinatesFromNumber($street, $streetNumber);

        if (!$coordinates) {
            return null;
        }

        // Generate the SW address
        $swAddress = $this->streetService->generateSwAddress($street, $streetNumber);

        return [
            'swAddress' => $swAddress,
            'streetNumber' => $streetNumber,
            'distanceOnStreet' => $coordinates['distanceOnStreet'],
            'streetSide' => $coordinates['streetSide'],
            'coordinates' => [
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
            ],
            'street' => [
                'id' => $street->id,
                'osmId' => $street->osm_id,
                'code' => $street->code,
                'displayName' => $street->display_name,
                'communeName' => $street->commune_name,
                'communeNumber' => $street->commune_number,
            ],
            'isCalculated' => true,
            'message' => 'Adresse calculée - aucune adresse enregistrée trouvée à cette position',
        ];
    }

    /**
     * Search by street number + street name (645 Rue de la Joie)
     */
    protected function searchStreetFormat(string $number, string $streetName, int $limit): array
    {
        $streetName = trim($streetName);

        // Search addresses with matching street number and street name
        $addresses = Address::with('street')
            ->where('street_number', $number)
            ->where(function ($q) use ($streetName) {
                $q->whereHas('street', function ($sq) use ($streetName) {
                    $sq->where('display_name', 'LIKE', "%{$streetName}%");
                })
                ->orWhere('official_address', 'LIKE', "%{$streetName}%")
                ->orWhere('display_name', 'LIKE', "%{$streetName}%");
            })
            ->limit($limit)
            ->get();

        // If no results with exact number, search by street name only
        if ($addresses->isEmpty()) {
            $addresses = Address::with('street')
                ->where(function ($q) use ($streetName, $number) {
                    $q->whereHas('street', function ($sq) use ($streetName) {
                        $sq->where('display_name', 'LIKE', "%{$streetName}%");
                    })
                    ->orWhere('official_address', 'LIKE', "%{$number}%{$streetName}%")
                    ->orWhere('display_name', 'LIKE', "%{$streetName}%");
                })
                ->limit($limit)
                ->get();
        }

        return [
            'format' => 'street',
            'parsed' => [
                'streetNumber' => $number,
                'streetName' => $streetName,
            ],
            'addresses' => $addresses,
        ];
    }

    /**
     * General search across all fields
     */
    protected function searchGeneral(string $query, int $limit): array
    {
        $addresses = Address::with('street')
            ->where(function ($q) use ($query) {
                $q->where('sw_address', 'LIKE', "%{$query}%")
                  ->orWhere('display_name', 'LIKE', "%{$query}%")
                  ->orWhere('quarter', 'LIKE', "%{$query}%")
                  ->orWhere('sub_quarter', 'LIKE', "%{$query}%")
                  ->orWhere('lieu_dit', 'LIKE', "%{$query}%")
                  ->orWhere('official_address', 'LIKE', "%{$query}%")
                  ->orWhere('street_number', $query)
                  ->orWhereHas('street', function ($sq) use ($query) {
                      $sq->where('display_name', 'LIKE', "%{$query}%")
                         ->orWhere('code', 'LIKE', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();

        return [
            'format' => 'general',
            'parsed' => ['query' => $query],
            'addresses' => $addresses,
        ];
    }

    /**
     * Update address itinerary (custom path to the address)
     */
    public function updateItinerary(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'itinerary' => 'required|array|min:1', // Minimum 1 point (start on known road)
            'itinerary.*.lat' => 'required|numeric|between:-90,90',
            'itinerary.*.lng' => 'required|numeric|between:-180,180',
            'itinerary.*.order' => 'nullable|integer|min:0',
            'itineraryStreetId' => 'nullable|exists:streets,id',
            'itineraryDescription' => 'nullable|string|max:1000',
            'intersectionId' => 'nullable|exists:intersections,id',
            'intersectionName' => 'nullable|string|max:255',
            'transportModes' => 'nullable|array',
            'transportModes.*' => 'string|in:walk,moto,taxi',
        ]);

        // Sort points by order if provided, otherwise use array order
        $points = collect($request->itinerary)->map(function ($point, $index) {
            return [
                'lat' => (float) $point['lat'],
                'lng' => (float) $point['lng'],
                'order' => $point['order'] ?? $index,
            ];
        })->sortBy('order')->values()->toArray();

        $address->update([
            'itinerary' => $points,
            'itinerary_street_id' => $request->itineraryStreetId,
            'itinerary_description' => $request->itineraryDescription,
            'itinerary_intersection_id' => $request->intersectionId,
            'itinerary_intersection_name' => $request->intersectionName,
            'itinerary_transport_modes' => $request->transportModes,
        ]);

        // Calculate and save distance
        $distance = $address->calculateItineraryDistance();
        if ($distance) {
            $address->update(['itinerary_distance' => $distance]);
        }

        $address->load(['street', 'itineraryStreet', 'itineraryIntersection']);

        return $this->success($this->formatAddress($address), 'Itinerary updated successfully');
    }

    /**
     * Delete address itinerary
     */
    public function deleteItinerary(Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $address->update([
            'itinerary' => null,
            'itinerary_street_id' => null,
            'itinerary_description' => null,
            'itinerary_distance' => null,
            'itinerary_intersection_id' => null,
            'itinerary_intersection_name' => null,
            'itinerary_transport_modes' => null,
        ]);

        return $this->success(null, 'Itinerary deleted successfully');
    }

    protected function formatAddress(Address $address): array
    {
        $data = [
            'id' => $address->id,
            'swAddress' => $address->sw_address,
            'displayName' => $address->display_name,
            'latLon' => $address->lat_lon,
            'coordinates' => $address->coordinates,
            'localization' => $address->localization,
            'way' => $address->way,
            'houseType' => $address->house_type,
            'homeStatus' => $address->home_status,
            'isNonHabitation' => $address->is_non_habitation,
            'residentName' => $address->resident_name,
            'honorDeclaration' => $address->honor_declaration,
            'description' => $address->description,
            'verificationStatus' => $address->verification_status,
            'shareUrl' => $address->getShareUrl(),
            'createdAt' => $address->created_at->toIso8601String(),
            'updatedAt' => $address->updated_at->toIso8601String(),
        ];

        // Add itinerary data if present
        if ($address->hasItinerary()) {
            $data['itinerary'] = [
                'points' => $address->itinerary,
                'pointsCount' => count($address->itinerary),
                'description' => $address->itinerary_description,
                'distance' => $address->itinerary_distance,
                'distanceFormatted' => $address->itinerary_distance
                    ? ($address->itinerary_distance >= 1000
                        ? number_format($address->itinerary_distance / 1000, 2) . ' km'
                        : $address->itinerary_distance . ' m')
                    : null,
                'destinationStreet' => $address->relationLoaded('itineraryStreet') && $address->itineraryStreet
                    ? [
                        'id' => $address->itineraryStreet->id,
                        'code' => $address->itineraryStreet->code,
                        'displayName' => $address->itineraryStreet->display_name,
                    ]
                    : null,
                'intersection' => $address->relationLoaded('itineraryIntersection') && $address->itineraryIntersection
                    ? [
                        'id' => $address->itineraryIntersection->id,
                        'name' => $address->itineraryIntersection->name,
                        'lat' => $address->itineraryIntersection->lat,
                        'lng' => $address->itineraryIntersection->lng,
                    ]
                    : null,
                'intersectionId' => $address->itinerary_intersection_id,
                'intersectionName' => $address->itinerary_intersection_name,
                'transportModes' => $address->itinerary_transport_modes,
            ];
        } else {
            $data['itinerary'] = null;
        }

        return $data;
    }

    protected function formatAddresses($addresses): array
    {
        return $addresses->map(fn($a) => $this->formatAddress($a))->toArray();
    }

    /**
     * Generate a unique domiciliation name for the user
     * Returns "Maison", "Maison 2", "Maison 3", etc.
     */
    protected function generateDomiciliationName(int $userId): string
    {
        $baseName = 'Maison';

        // Get all existing domiciliation names for this user that start with "Maison"
        $existingNames = Domiciliation::where('user_id', $userId)
            ->where('name', 'LIKE', $baseName . '%')
            ->pluck('name')
            ->toArray();

        // If no "Maison" exists, return "Maison"
        if (!in_array($baseName, $existingNames)) {
            return $baseName;
        }

        // Find the next available number
        $maxNumber = 1;
        foreach ($existingNames as $name) {
            if ($name === $baseName) {
                continue;
            }
            // Extract number from "Maison X"
            if (preg_match('/^' . preg_quote($baseName) . '\s+(\d+)$/', $name, $matches)) {
                $number = (int) $matches[1];
                if ($number >= $maxNumber) {
                    $maxNumber = $number + 1;
                }
            }
        }

        // If only "Maison" exists without number, start at 2
        if ($maxNumber === 1) {
            $maxNumber = 2;
        }

        return $baseName . ' ' . $maxNumber;
    }
}
