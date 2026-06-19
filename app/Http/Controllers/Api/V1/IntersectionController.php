<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Intersection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntersectionController extends Controller
{
    /**
     * Search for intersections nearby a given location.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:100', 'max:5000'],
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radius = (int) ($request->radius ?? 500);

        $intersections = Intersection::nearby($lat, $lng, $radius)
            ->limit(20)
            ->get()
            ->map(function ($intersection) {
                return [
                    'id' => $intersection->id,
                    'name' => $intersection->name,
                    'lat' => $intersection->lat,
                    'lng' => $intersection->lng,
                    'distance' => (int) round($intersection->distance),
                ];
            });

        return response()->json([
            'data' => $intersections,
        ]);
    }

    /**
     * Store a new intersection.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'quarter' => ['nullable', 'string', 'max:100'],
        ]);

        $intersection = Intersection::create($validated);

        return response()->json([
            'message' => 'Intersection créée avec succès',
            'data' => [
                'id' => $intersection->id,
                'name' => $intersection->name,
                'lat' => $intersection->lat,
                'lng' => $intersection->lng,
            ],
        ], 201);
    }
}
