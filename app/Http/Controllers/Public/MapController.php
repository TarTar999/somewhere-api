<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/map', [
            'initialCenter' => [3.848, 11.5021], // Yaounde, Cameroon
            'mapConfig' => [
                'zoom' => 13,
                'maxZoom' => 19,
                'tileUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric|max:50', // km
        ]);

        $query = $request->get('q');
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $radius = $request->get('radius', 5); // Default 5km

        $addresses = Address::query()
            ->with('street:id,display_name,commune')
            ->where('verification_status', 'approved');

        // Search by SW address or text
        if ($query) {
            $addresses->where(function ($q) use ($query) {
                $q->where('sw_address', 'like', "%{$query}%")
                  ->orWhere('lieu_dit', 'like', "%{$query}%")
                  ->orWhere('quarter', 'like', "%{$query}%")
                  ->orWhere('sub_quarter', 'like', "%{$query}%");
            });
        }

        // Search by proximity
        if ($lat && $lng) {
            $addresses->selectRaw('*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance', [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
        }

        $results = $addresses->limit(20)->get()->map(fn ($address) => [
            'id' => $address->id,
            'swAddress' => $address->sw_address,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'streetName' => $address->street?->display_name,
            'lieuDit' => $address->lieu_dit,
            'quarter' => $address->quarter,
            'commune' => $address->street?->commune,
            'distance' => isset($address->distance) ? round($address->distance, 2) : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function showAddress(Address $address): Response
    {
        $address->load(['street', 'user:id,first_name,last_name']);

        return Inertia::render('public/address', [
            'address' => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'streetNumber' => $address->street_number,
                'streetName' => $address->street?->display_name,
                'lieuDit' => $address->lieu_dit,
                'quarter' => $address->quarter,
                'subQuarter' => $address->sub_quarter,
                'commune' => $address->street?->commune,
                'houseType' => $address->house_type,
                'homeStatus' => $address->home_status,
                'verificationStatus' => $address->verification_status,
                'owner' => $address->user ? [
                    'name' => $address->user->full_name,
                ] : null,
            ],
            'mapConfig' => [
                'center' => [$address->latitude, $address->longitude],
                'zoom' => 17,
            ],
        ]);
    }
}
