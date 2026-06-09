<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    public function edit(): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        return Inertia::render('company/settings/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'legalName' => $company->legal_name,
                'registrationNumber' => $company->registration_number,
                'taxId' => $company->tax_id,
                'description' => $company->description,
                'address' => $company->address,
                'city' => $company->city,
                'country' => $company->country,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'status' => $company->status,
            ],
            'userRole' => $user->getCompanyRole($company),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'legal_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048',
        ]);

        $company = auth()->user()->currentCompany;

        $data = $request->except('logo');

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        try {
            $this->companyService->update($company, $data);

            return back()->with('success', 'Paramètres mis à jour');
        } catch (\Exception $e) {
            return back()->withErrors(['settings' => $e->getMessage()]);
        }
    }
}
