<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Services\LabelService;
use App\Services\ZoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZoneController extends Controller
{
    public function __construct(
        protected ZoneService $zoneService,
        protected LabelService $labelService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $filters = $request->only(['status', 'zone_type', 'parent_id', 'search']);
        $zones = $this->zoneService->getCompanyZones($company, $filters);
        $labels = $this->labelService->getCompanyLabels($company);

        return Inertia::render('company/zones/index', [
            'zones' => $zones->map(fn ($zone) => $this->formatZone($zone)),
            'labels' => $labels->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'color' => $l->color,
                'icon' => $l->icon,
            ]),
            'filters' => $filters,
            'stats' => [
                'total' => $zones->count(),
                'active' => $zones->where('status', 'active')->count(),
                'inactive' => $zones->where('status', 'inactive')->count(),
                'circles' => $zones->where('zone_type', 'circle')->count(),
                'polygons' => $zones->where('zone_type', 'polygon')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $labels = $this->labelService->getCompanyLabels($company);
        $parentZones = $company->zones()->whereNull('parent_zone_id')->get();

        return Inertia::render('company/zones/create', [
            'labels' => $labels->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'color' => $l->color,
                'icon' => $l->icon,
            ]),
            'parentZones' => $parentZones->map(fn ($z) => [
                'id' => $z->id,
                'name' => $z->name,
            ]),
            'defaultColors' => $this->labelService->getDefaultColors(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'zone_type' => 'required|in:circle,polygon',
            'parent_zone_id' => 'nullable|exists:zones,id',

            'center_lat' => 'required_if:zone_type,circle|nullable|numeric|between:-90,90',
            'center_lng' => 'required_if:zone_type,circle|nullable|numeric|between:-180,180',
            'radius_meters' => 'required_if:zone_type,circle|nullable|integer|min:10|max:100000',

            'polygon_coordinates' => 'required_if:zone_type,polygon|nullable|array|min:3',

            'fill_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'fill_opacity' => 'nullable|numeric|between:0,1',
            'stroke_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'stroke_width' => 'nullable|integer|between:1,10',

            'label_ids' => 'nullable|array',
        ]);

        $zone = $this->zoneService->create($company, $validated, $user);

        if (!empty($validated['label_ids'])) {
            $this->zoneService->syncLabels($zone, $validated['label_ids']);
        }

        return redirect()
            ->route('company.zones.show', $zone)
            ->with('success', 'Zone créée avec succès');
    }

    public function show(Zone $zone): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($zone->company_id !== $company->id) {
            abort(404);
        }

        $zone->load(['labels', 'parent', 'children', 'creator']);
        $stats = $this->zoneService->getZoneStatistics($zone);

        return Inertia::render('company/zones/show', [
            'zone' => $this->formatZone($zone, true),
            'statistics' => $stats,
            'geoJson' => $zone->toGeoJson(),
        ]);
    }

    public function edit(Zone $zone): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($zone->company_id !== $company->id) {
            abort(404);
        }

        $zone->load('labels');
        $labels = $this->labelService->getCompanyLabels($company);
        $parentZones = $company->zones()
            ->whereNull('parent_zone_id')
            ->where('id', '!=', $zone->id)
            ->get();

        return Inertia::render('company/zones/edit', [
            'zone' => $this->formatZone($zone, true),
            'labels' => $labels->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'color' => $l->color,
                'icon' => $l->icon,
            ]),
            'parentZones' => $parentZones->map(fn ($z) => [
                'id' => $z->id,
                'name' => $z->name,
            ]),
            'defaultColors' => $this->labelService->getDefaultColors(),
        ]);
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($zone->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
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
        ]);

        $this->zoneService->update($zone, $validated);

        if (array_key_exists('label_ids', $validated)) {
            $this->zoneService->syncLabels($zone, $validated['label_ids'] ?? []);
        }

        return redirect()
            ->route('company.zones.show', $zone)
            ->with('success', 'Zone mise à jour avec succès');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($zone->company_id !== $company->id) {
            abort(404);
        }

        $this->zoneService->delete($zone);

        return redirect()
            ->route('company.zones.index')
            ->with('success', 'Zone supprimée avec succès');
    }

    public function duplicate(Zone $zone): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($zone->company_id !== $company->id) {
            abort(404);
        }

        $newZone = $this->zoneService->duplicateZone($zone);

        return redirect()
            ->route('company.zones.show', $newZone)
            ->with('success', 'Zone dupliquée avec succès');
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
            'centerLat' => $zone->center_lat ? (float) $zone->center_lat : null,
            'centerLng' => $zone->center_lng ? (float) $zone->center_lng : null,
            'radiusMeters' => $zone->radius_meters,
            'polygonCoordinates' => $zone->polygon_coordinates,
            'fillColor' => $zone->fill_color,
            'fillOpacity' => (float) $zone->fill_opacity,
            'strokeColor' => $zone->stroke_color,
            'strokeWidth' => $zone->stroke_width,
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
                ? ['id' => $zone->parent->id, 'name' => $zone->parent->name]
                : null;

            $data['children'] = $zone->relationLoaded('children')
                ? $zone->children->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'status' => $c->status,
                ])
                : [];

            $data['creator'] = $zone->relationLoaded('creator') && $zone->creator
                ? ['id' => $zone->creator->id, 'name' => $zone->creator->full_name]
                : null;

            $data['metadata'] = $zone->metadata;
            $data['updatedAt'] = $zone->updated_at->toIso8601String();
        }

        return $data;
    }
}
