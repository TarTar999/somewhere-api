<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SelectionController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $companies = $user->activeCompanies()
            ->with('activeSubscription')
            ->get()
            ->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'role' => $company->pivot->role,
                'membersCount' => $company->getMemberCount(),
                'hasActiveSubscription' => $company->hasActiveSubscription(),
            ]);

        return Inertia::render('company/select', [
            'companies' => $companies,
            'currentCompanyId' => $user->current_company_id,
        ]);
    }

    public function select(Company $company): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->switchCompany($company)) {
            return back()->withErrors(['company' => 'Impossible de sélectionner cette entreprise']);
        }

        return redirect()->route('company.dashboard');
    }
}
