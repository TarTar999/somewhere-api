<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Label;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class LabelService
{
    public function create(Company $company, array $data): Label
    {
        $slug = Str::slug($data['name']);
        $existingCount = Label::where('company_id', $company->id)
            ->where('slug', 'like', $slug . '%')
            ->count();

        if ($existingCount > 0) {
            $slug = $slug . '-' . ($existingCount + 1);
        }

        return Label::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? '#3B82F6',
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Label $label, array $data): Label
    {
        $updateData = [];

        if (isset($data['name']) && $data['name'] !== $label->name) {
            $updateData['name'] = $data['name'];
            $slug = Str::slug($data['name']);
            $existingCount = Label::where('company_id', $label->company_id)
                ->where('id', '!=', $label->id)
                ->where('slug', 'like', $slug . '%')
                ->count();
            $updateData['slug'] = $existingCount > 0 ? $slug . '-' . ($existingCount + 1) : $slug;
        }

        foreach (['color', 'icon', 'description'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $label->update($updateData);

        return $label->fresh();
    }

    public function delete(Label $label): void
    {
        $label->delete();
    }

    public function getCompanyLabels(Company $company, array $filters = []): Collection
    {
        $query = $company->labels();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    public function getLabelsWithUsageCount(Company $company): Collection
    {
        return $company->labels()
            ->withCount(['zones', 'addresses'])
            ->orderBy('name')
            ->get();
    }

    public function findBySlug(Company $company, string $slug): ?Label
    {
        return $company->labels()->where('slug', $slug)->first();
    }

    public function bulkCreate(Company $company, array $labelsData): Collection
    {
        $created = collect();

        foreach ($labelsData as $data) {
            $created->push($this->create($company, $data));
        }

        return $created;
    }

    public function bulkDelete(Company $company, array $labelIds): int
    {
        return $company->labels()
            ->whereIn('id', $labelIds)
            ->delete();
    }

    public function getAvailableIcons(): array
    {
        return [
            'pin', 'star', 'heart', 'flag', 'bookmark',
            'building', 'home', 'office', 'store', 'warehouse',
            'truck', 'car', 'bike', 'walking', 'plane',
            'user', 'users', 'briefcase', 'shield', 'key',
            'dollar', 'euro', 'creditcard', 'wallet', 'bank',
            'package', 'box', 'archive', 'folder', 'file',
            'alert', 'info', 'check', 'clock', 'calendar',
            'phone', 'mail', 'message', 'bell', 'camera',
            'map', 'globe', 'compass', 'location', 'target',
        ];
    }

    public function getDefaultColors(): array
    {
        return [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // violet
            '#EC4899', // pink
            '#06B6D4', // cyan
            '#84CC16', // lime
            '#F97316', // orange
            '#6366F1', // indigo
        ];
    }
}
