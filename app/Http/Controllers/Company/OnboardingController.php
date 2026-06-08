<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    public function create(): Response
    {
        return Inertia::render('company/create', [
            'plans' => collect(config('company.plans', []))->map(function ($plan, $code) {
                return [
                    'code' => $code,
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'priceFormatted' => number_format($plan['price']) . ' XAF/mois',
                    'maxMembers' => $plan['max_members'],
                    'documentsPerMonth' => $plan['documents_per_month'],
                    'features' => $plan['features'],
                ];
            })->values()->toArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'legal_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);

        try {
            $company = $this->companyService->create(
                $request->all(),
                auth()->user()
            );

            return redirect()->route('company.subscription.plans')
                ->with('success', 'Entreprise créée avec succès. Choisissez votre plan.');
        } catch (\Exception $e) {
            return back()->withErrors(['company' => $e->getMessage()]);
        }
    }
}
