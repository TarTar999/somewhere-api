<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Models\Zone;
use App\Services\ZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyZoneController extends Controller
{
    public function __construct(
        protected ZoneService $zoneService
    ) {}

    public function index(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        $filters = $request->only(['status', 'zone_type', 'parent_id', 'label_ids']);
        $zones = $this->zoneService->getCompanyZones($company, $filters);

        return $this->success($zones->map(fn ($zone) => $this->formatZone($zone)));
    }

    public function store(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can create zones', 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'zone_type' => 'required|in:circle,polygon',
            'parent_zone_id' => 'nullable|exists:zones,id',

            // Circle fields
            'center_lat' => 'required_if:zone_type,circle|nullable|numeric|between:-90,90',
            'center_lng' => 'required_if:zone_type,circle|nullable|numeric|between:-180,180',
            'radius_meters' => 'required_if:zone_type,circle|nullable|integer|min:10|max:100000',

            // Polygon fields
            'polygon_coordinates' => 'required_if:zone_type,polygon|nullable|array|min:3',
            'polygon_coordinates.*.lat' => 'required_with:polygon_coordinates|numeric|between:-90,90',
            'polygon_coordinates.*.lng' => 'required_with:polygon_coordinates|numeric|between:-180,180',

            // Styling
            'fill_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'fill_opacity' => 'nullable|numeric|between:0,1',
            'stroke_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'stroke_width' => 'nullable|integer|between:1,10',

            // Labels
            'label_ids' => 'nullable|array',
            'label_ids.*' => 'exists:labels,id',

            'metadata' => 'nullable|array',
        ]);

        try {
            $zone = $this->zoneService->create($company, $request->all(), $user);

            if ($request->has('label_ids')) {
                $this->zoneService->syncLabels($zone, $request->input('label_ids'));
                $zone->load('labels');
            }

            return $this->success(
                $this->formatZone($zone, true),
                'Zone created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(Company $company, Zone $zone): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        if ($zone->company_id !== $company->id) {
            return $this->error('Zone not found', 404);
        }

        $zone->load(['labels', 'parent', 'children', 'creator']);

        return $this->success($this->formatZone($zone, true));
    }

    public function update(Request $request, Company $company, Zone $zone): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can update zones', 403);
        }

        if ($zone->company_id !== $company->id) {
            return $this->error('Zone not found', 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
            'zone_type' => 'sometimes|in:circle,polygon',
            'parent_zone_id' => 'nullable|exists:zones,id',

            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lng' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:100000',
            'polygon_coordinates' => 'nullable|array|min:3',

            'fill_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'fill_opacity' => 'nullable|numeric|between:0,1',
            'stroke_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'stroke_width' => 'nullable|integer|between:1,10',

            'status' => 'sometimes|in:active,inactive,archived',
            'label_ids' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        // Prevent zone from being its own parent
        if ($request->has('parent_zone_id') && $request->input('parent_zone_id') == $zone->id) {
            return $this->error('A zone cannot be its own parent', 400);
        }

        try {
            $zone = $this->zoneService->update($zone, $request->all());

            if ($request->has('label_ids')) {
                $this->zoneService->syncLabels($zone, $request->input('label_ids'));
            }

            $zone->load(['labels', 'parent']);

            return $this->success(
                $this->formatZone($zone, true),
                'Zone updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Company $company, Zone $zone): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can delete zones', 403);
        }

        if ($zone->company_id !== $company->id) {
            return $this->error('Zone not found', 404);
        }

        $this->zoneService->delete($zone);

        return $this->success(null, 'Zone deleted successfully');
    }

    public function hierarchy(Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        $hierarchy = $this->zoneService->getZoneHierarchy($company);

        return $this->success($hierarchy);
    }

    public function children(Company $company, Zone $zone): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        if ($zone->company_id !== $company->id) {
            return $this->error('Zone not found', 404);
        }

        $children = $zone->children()->with('labels')->get();

        return $this->success($children->map(fn ($z) => $this->formatZone($z)));
    }

    public function containsPoint(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $zones = $this->zoneService->findZonesContainingPoint(
            $company,
            $request->input('lat'),
            $request->input('lng')
        );

        return $this->success($zones->map(fn ($zone) => $this->formatZone($zone)));
    }

    public function inBounds(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        $request->validate([
            'north' => 'required|numeric|between:-90,90',
            'south' => 'required|numeric|between:-90,90',
            'east' => 'required|numeric|between:-180,180',
            'west' => 'required|numeric|between:-180,180',
        ]);

        $zones = $this->zoneService->findZonesInBoundingBox(
            $company,
            $request->input('north'),
            $request->input('south'),
            $request->input('east'),
            $request->input('west')
        );

        return $this->success($zones->map(fn ($zone) => $this->formatZone($zone)));
    }

    public function statistics(Company $company, Zone $zone): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        if ($zone->company_id !== $company->id) {
            return $this->error('Zone not found', 404);
        }

        $stats = $this->zoneService->getZoneStatistics($zone);

        return $this->success($stats);
    }

    public function duplicate(Company $company, Zone $zone): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can duplicate zones', 403);
        }

        if ($zone->company_id !== $company->id) {
            return $this->error('Zone not found', 404);
        }

        $newZone = $this->zoneService->duplicateZone($zone);
        $newZone->load('labels');

        return $this->success(
            $this->formatZone($newZone, true),
            'Zone duplicated successfully',
            201
        );
    }

    public function exportGeoJson(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        $zoneIds = $request->has('zone_ids')
            ? explode(',', $request->input('zone_ids'))
            : null;

        $geoJson = $this->zoneService->exportToGeoJson($company, $zoneIds);

        return response()->json($geoJson);
    }

    protected function formatZone(Zone $zone, bool $detailed = false): array
    {
        $data = [
            'id' => $zone->id,
            'name' => $zone->name,
            'slug' => $zone->slug,
            'description' => $zone->description,
            'zoneType' => $zone->zone_type,
            'status' => $zone->status,

            // Geometry
            'centerLat' => $zone->center_lat ? (float) $zone->center_lat : null,
            'centerLng' => $zone->center_lng ? (float) $zone->center_lng : null,
            'radiusMeters' => $zone->radius_meters,
            'polygonCoordinates' => $zone->polygon_coordinates,

            // Styling
            'fillColor' => $zone->fill_color,
            'fillOpacity' => (float) $zone->fill_opacity,
            'strokeColor' => $zone->stroke_color,
            'strokeWidth' => $zone->stroke_width,

            // Relations
            'parentZoneId' => $zone->parent_zone_id,
            'labels' => $zone->relationLoaded('labels')
                ? $zone->labels->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'color' => $l->color,
                    'icon' => $l->icon,
                ])
                : [],

            'createdAt' => $zone->created_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['parent'] = $zone->relationLoaded('parent') && $zone->parent
                ? [
                    'id' => $zone->parent->id,
                    'name' => $zone->parent->name,
                ]
                : null;

            $data['childrenCount'] = $zone->relationLoaded('children')
                ? $zone->children->count()
                : $zone->children()->count();

            $data['creator'] = $zone->relationLoaded('creator') && $zone->creator
                ? [
                    'id' => $zone->creator->id,
                    'name' => $zone->creator->full_name,
                ]
                : null;

            $data['metadata'] = $zone->metadata;
            $data['boundingBox'] = $zone->getBoundingBox();
            $data['updatedAt'] = $zone->updated_at->toIso8601String();
        }

        return $data;
    }
}
