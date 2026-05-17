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
            ->with('street')
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
                'honor_declaration' => true,
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

        $address->load('street');

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

        return $this->success([
            'query' => $query,
            'format' => $searchResult['format'],
            'parsed' => $searchResult['parsed'],
            'results' => $this->formatAddresses($searchResult['addresses']),
            'count' => $searchResult['addresses']->count(),
        ]);
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
     */
    protected function searchSwFormat(string $query, int $limit): array
    {
        // Remove @ prefix
        $swAddress = ltrim($query, '@');

        // Parse SW address: "370 Rue 3.2" → number=370, code=3.2
        $parsed = [];
        if (preg_match('/^(\d+)\s+(?:Rue|Ave|Avenue|Blvd|Boulevard)?\s*(\d+\.[A-Z0-9]+)$/i', $swAddress, $matches)) {
            $parsed = [
                'streetNumber' => $matches[1],
                'streetCode' => $matches[2],
            ];
        }

        // Search by sw_address (exact or partial match)
        $addresses = Address::with('street')
            ->where(function ($q) use ($swAddress, $query) {
                $q->where('sw_address', 'LIKE', "%{$swAddress}%")
                  ->orWhere('sw_address', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        // If no results and we have parsed data, search by street code
        if ($addresses->isEmpty() && !empty($parsed['streetCode'])) {
            $addresses = Address::with('street')
                ->whereHas('street', function ($q) use ($parsed) {
                    $q->where('code', $parsed['streetCode']);
                })
                ->where(function ($q) use ($parsed) {
                    if (!empty($parsed['streetNumber'])) {
                        $q->where('street_number', $parsed['streetNumber']);
                    }
                })
                ->limit($limit)
                ->get();
        }

        return [
            'format' => 'somewhere',
            'parsed' => $parsed,
            'addresses' => $addresses,
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

    protected function formatAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'swAddress' => $address->sw_address,
            'displayName' => $address->display_name,
            'latLon' => $address->lat_lon,
            'coordinates' => $address->coordinates,
            'localization' => $address->localization,
            'way' => $address->way,
            'houseType' => $address->house_type,
            'homeStatus' => $address->home_status,
            'description' => $address->description,
            'verificationStatus' => $address->verification_status,
            'shareUrl' => $address->getShareUrl(),
            'createdAt' => $address->created_at->toISOString(),
            'updatedAt' => $address->updated_at->toISOString(),
        ];
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
