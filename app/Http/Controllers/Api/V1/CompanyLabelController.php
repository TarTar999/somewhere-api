<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Models\Label;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyLabelController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    public function index(Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        $labels = $this->labelService->getLabelsWithUsageCount($company);

        return $this->success($labels->map(fn ($label) => $this->formatLabel($label)));
    }

    public function store(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can create labels', 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $label = $this->labelService->create($company, $request->all());

            return $this->success(
                $this->formatLabel($label),
                'Label created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(Company $company, Label $label): JsonResponse
    {
        $user = auth()->user();

        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        if ($label->company_id !== $company->id) {
            return $this->error('Label not found', 404);
        }

        $label->loadCount(['zones', 'addresses']);

        return $this->success($this->formatLabel($label, true));
    }

    public function update(Request $request, Company $company, Label $label): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can update labels', 403);
        }

        if ($label->company_id !== $company->id) {
            return $this->error('Label not found', 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $label = $this->labelService->update($label, $request->all());

            return $this->success(
                $this->formatLabel($label),
                'Label updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Company $company, Label $label): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can delete labels', 403);
        }

        if ($label->company_id !== $company->id) {
            return $this->error('Label not found', 404);
        }

        $this->labelService->delete($label);

        return $this->success(null, 'Label deleted successfully');
    }

    public function bulkCreate(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can create labels', 403);
        }

        $request->validate([
            'labels' => 'required|array|min:1|max:50',
            'labels.*.name' => 'required|string|max:100',
            'labels.*.color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'labels.*.icon' => 'nullable|string|max:50',
            'labels.*.description' => 'nullable|string|max:500',
        ]);

        try {
            $labels = $this->labelService->bulkCreate($company, $request->input('labels'));

            return $this->success(
                $labels->map(fn ($label) => $this->formatLabel($label)),
                'Labels created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function bulkDelete(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can delete labels', 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $deleted = $this->labelService->bulkDelete($company, $request->input('ids'));

        return $this->success(
            ['deleted_count' => $deleted],
            "{$deleted} label(s) deleted successfully"
        );
    }

    public function icons(): JsonResponse
    {
        return $this->success([
            'icons' => $this->labelService->getAvailableIcons(),
            'colors' => $this->labelService->getDefaultColors(),
        ]);
    }

    protected function formatLabel(Label $label, bool $detailed = false): array
    {
        $data = [
            'id' => $label->id,
            'name' => $label->name,
            'slug' => $label->slug,
            'color' => $label->color,
            'icon' => $label->icon,
            'description' => $label->description,
            'zonesCount' => $label->zones_count ?? 0,
            'addressesCount' => $label->addresses_count ?? 0,
            'createdAt' => $label->created_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['updatedAt'] = $label->updated_at->toIso8601String();
        }

        return $data;
    }
}
