<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ZoneService
{
    public function __construct(
        private GeoService $geoService
    ) {}

    public function create(Company $company, array $data, ?User $createdBy = null): Zone
    {
        $slug = Str::slug($data['name']);
        $existingCount = Zone::where('company_id', $company->id)
            ->where('slug', 'like', $slug . '%')
            ->count();

        if ($existingCount > 0) {
            $slug = $slug . '-' . ($existingCount + 1);
        }

        return Zone::create([
            'company_id' => $company->id,
            'parent_zone_id' => $data['parent_zone_id'] ?? null,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'zone_type' => $data['zone_type'] ?? Zone::TYPE_CIRCLE,
            'center_lat' => $data['center_lat'] ?? null,
            'center_lng' => $data['center_lng'] ?? null,
            'radius_meters' => $data['radius_meters'] ?? null,
            'polygon_coordinates' => $data['polygon_coordinates'] ?? null,
            'fill_color' => $data['fill_color'] ?? '#3B82F6',
            'fill_opacity' => $data['fill_opacity'] ?? 0.3,
            'stroke_color' => $data['stroke_color'] ?? '#2563EB',
            'stroke_width' => $data['stroke_width'] ?? 2,
            'status' => $data['status'] ?? Zone::STATUS_ACTIVE,
            'metadata' => $data['metadata'] ?? null,
            'created_by' => $createdBy?->id,
        ]);
    }

    public function update(Zone $zone, array $data): Zone
    {
        $updateData = [];

        if (isset($data['name']) && $data['name'] !== $zone->name) {
            $updateData['name'] = $data['name'];
            $slug = Str::slug($data['name']);
            $existingCount = Zone::where('company_id', $zone->company_id)
                ->where('id', '!=', $zone->id)
                ->where('slug', 'like', $slug . '%')
                ->count();
            $updateData['slug'] = $existingCount > 0 ? $slug . '-' . ($existingCount + 1) : $slug;
        }

        $fields = [
            'parent_zone_id', 'description', 'zone_type',
            'center_lat', 'center_lng', 'radius_meters', 'polygon_coordinates',
            'fill_color', 'fill_opacity', 'stroke_color', 'stroke_width',
            'status', 'metadata'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $zone->update($updateData);

        return $zone->fresh();
    }

    public function delete(Zone $zone): void
    {
        // Reassign children to parent if exists
        if ($zone->children()->exists()) {
            $zone->children()->update(['parent_zone_id' => $zone->parent_zone_id]);
        }

        $zone->delete();
    }

    public function getCompanyZones(Company $company, array $filters = []): Collection
    {
        $query = $company->zones()->with(['labels', 'parent', 'creator']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['zone_type'])) {
            $query->where('zone_type', $filters['zone_type']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'root') {
                $query->whereNull('parent_zone_id');
            } else {
                $query->where('parent_zone_id', $filters['parent_id']);
            }
        }

        if (isset($filters['label_ids']) && is_array($filters['label_ids'])) {
            $query->whereHas('labels', function ($q) use ($filters) {
                $q->whereIn('labels.id', $filters['label_ids']);
            });
        }

        return $query->orderBy('name')->get();
    }

    public function findZonesContainingPoint(Company $company, float $lat, float $lng): Collection
    {
        $zones = $company->activeZones()->get();

        return $zones->filter(function (Zone $zone) use ($lat, $lng) {
            return $zone->containsPoint($lat, $lng);
        })->values();
    }

    public function findZonesInBoundingBox(
        Company $company,
        float $north,
        float $south,
        float $east,
        float $west
    ): Collection {
        $requestBox = compact('north', 'south', 'east', 'west');
        $zones = $company->activeZones()->get();

        return $zones->filter(function (Zone $zone) use ($requestBox) {
            $zoneBox = $zone->getBoundingBox();
            if (empty($zoneBox)) {
                return false;
            }
            return $this->geoService->doBoundingBoxesIntersect($zoneBox, $requestBox);
        })->values();
    }

    public function syncLabels(Zone $zone, array $labelIds): void
    {
        $zone->labels()->sync($labelIds);
    }

    public function getZoneHierarchy(Company $company): array
    {
        $zones = $company->zones()
            ->with(['labels'])
            ->whereNull('parent_zone_id')
            ->get();

        return $this->buildHierarchy($zones);
    }

    private function buildHierarchy(Collection $zones): array
    {
        return $zones->map(function (Zone $zone) {
            $children = $zone->children()->with(['labels'])->get();
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'slug' => $zone->slug,
                'zone_type' => $zone->zone_type,
                'status' => $zone->status,
                'labels' => $zone->labels->pluck('name'),
                'children' => $this->buildHierarchy($children),
            ];
        })->toArray();
    }

    public function getZoneStatistics(Zone $zone): array
    {
        $area = $zone->isCircle()
            ? $this->geoService->calculateCircleArea($zone->radius_meters)
            : ($zone->polygon_coordinates
                ? $this->geoService->calculatePolygonArea($zone->polygon_coordinates)
                : 0);

        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'zone_type' => $zone->zone_type,
            'area_sqm' => round($area, 2),
            'area_sqkm' => round($area / 1_000_000, 4),
            'children_count' => $zone->children()->count(),
            'labels_count' => $zone->labels()->count(),
            'bounding_box' => $zone->getBoundingBox(),
        ];
    }

    public function duplicateZone(Zone $zone, ?string $newName = null): Zone
    {
        $name = $newName ?? $zone->name . ' (copy)';

        $newZone = $this->create($zone->company, [
            'name' => $name,
            'description' => $zone->description,
            'zone_type' => $zone->zone_type,
            'center_lat' => $zone->center_lat,
            'center_lng' => $zone->center_lng,
            'radius_meters' => $zone->radius_meters,
            'polygon_coordinates' => $zone->polygon_coordinates,
            'fill_color' => $zone->fill_color,
            'fill_opacity' => $zone->fill_opacity,
            'stroke_color' => $zone->stroke_color,
            'stroke_width' => $zone->stroke_width,
            'parent_zone_id' => $zone->parent_zone_id,
            'metadata' => $zone->metadata,
        ]);

        // Copy labels
        $newZone->labels()->sync($zone->labels->pluck('id'));

        return $newZone;
    }

    public function exportToGeoJson(Company $company, ?array $zoneIds = null): array
    {
        $query = $company->zones()->with(['labels']);

        if ($zoneIds) {
            $query->whereIn('id', $zoneIds);
        }

        $zones = $query->get();

        return [
            'type' => 'FeatureCollection',
            'features' => $zones->map(fn(Zone $zone) => $zone->toGeoJson())->toArray(),
        ];
    }
}
