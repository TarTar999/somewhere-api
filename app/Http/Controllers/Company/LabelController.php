<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Services\LabelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabelController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $filters = $request->only(['search']);
        $labels = $this->labelService->getLabelsWithUsageCount($company);

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $labels = $labels->filter(function ($label) use ($search) {
                return str_contains(strtolower($label->name), $search) ||
                       str_contains(strtolower($label->description ?? ''), $search);
            })->values();
        }

        return Inertia::render('company/labels/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'status' => $company->status,
            ],
            'userRole' => $user->getCompanyRole($company),
            'labels' => $labels->map(fn ($label) => [
                'id' => $label->id,
                'name' => $label->name,
                'slug' => $label->slug,
                'color' => $label->color,
                'icon' => $label->icon,
                'description' => $label->description,
                'zonesCount' => $label->zones_count,
                'addressesCount' => $label->addresses_count,
                'createdAt' => $label->created_at->toIso8601String(),
            ]),
            'filters' => $filters,
            'availableIcons' => $this->labelService->getAvailableIcons(),
            'defaultColors' => $this->labelService->getDefaultColors(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $this->labelService->create($company, $validated);

        return redirect()
            ->back()
            ->with('success', 'Label créé avec succès');
    }

    public function update(Request $request, Label $label): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($label->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $this->labelService->update($label, $validated);

        return redirect()
            ->back()
            ->with('success', 'Label mis à jour avec succès');
    }

    public function destroy(Label $label): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if ($label->company_id !== $company->id) {
            abort(404);
        }

        $this->labelService->delete($label);

        return redirect()
            ->back()
            ->with('success', 'Label supprimé avec succès');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $deleted = $this->labelService->bulkDelete($company, $validated['ids']);

        return redirect()
            ->back()
            ->with('success', "{$deleted} label(s) supprimé(s) avec succès");
    }
}
