<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Services\CompanyService;
use App\Services\CompanySubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService,
        protected CompanySubscriptionService $subscriptionService
    ) {}

    public function index(): JsonResponse
    {
        $companies = auth()->user()->activeCompanies()
            ->with(['activeSubscription'])
            ->get()
            ->map(fn ($company) => $this->formatCompany($company));

        return $this->success($companies);
    }

    public function show(Company $company): JsonResponse
    {
        $user = auth()->user();

        // Check if user is a member
        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return $this->error('Not a member of this company', 403);
        }

        return $this->success($this->formatCompany($company, true));
    }

    public function store(Request $request): JsonResponse
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

            return $this->success(
                $this->formatCompany($company),
                'Company created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can update company details', 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'legal_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);

        try {
            $company = $this->companyService->update($company, $request->all());

            return $this->success(
                $this->formatCompany($company),
                'Company updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function current(): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        return $this->success([
            'company' => $this->formatCompany($company, true),
            'role' => $user->getCompanyRole($company),
            'usage' => $this->subscriptionService->getUsageStats($company),
        ]);
    }

    public function switchCompany(Company $company): JsonResponse
    {
        $user = auth()->user();

        if (!$user->switchCompany($company)) {
            return $this->error('Cannot switch to this company', 403);
        }

        return $this->success([
            'company' => $this->formatCompany($company),
            'role' => $user->getCompanyRole($company),
        ], 'Company switched successfully');
    }

    public function plans(): JsonResponse
    {
        $plans = config('company.plans', []);

        $formattedPlans = collect($plans)->map(function ($plan, $code) {
            return [
                'code' => $code,
                'name' => $plan['name'],
                'price' => $plan['price'],
                'priceFormatted' => number_format($plan['price']) . ' XAF',
                'maxMembers' => $plan['max_members'],
                'documentsPerMonth' => $plan['documents_per_month'],
                'features' => $plan['features'],
            ];
        })->values();

        return $this->success($formattedPlans);
    }

    protected function formatCompany(Company $company, bool $detailed = false): array
    {
        $data = [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'email' => $company->email,
            'phone' => $company->phone,
            'logoUrl' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
            'status' => $company->status,
            'membersCount' => $company->getMemberCount(),
            'createdAt' => $company->created_at->toIso8601String(),
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'legalName' => $company->legal_name,
                'registrationNumber' => $company->registration_number,
                'taxId' => $company->tax_id,
                'description' => $company->description,
                'address' => $company->address,
                'city' => $company->city,
                'country' => $company->country,
                'activatedAt' => $company->activated_at?->toIso8601String(),
                'subscription' => $company->activeSubscription ? [
                    'plan' => $company->activeSubscription->plan_code,
                    'status' => $company->activeSubscription->status,
                    'maxMembers' => $company->activeSubscription->max_members,
                    'documentsPerMonth' => $company->activeSubscription->documents_per_month,
                    'periodEnd' => $company->activeSubscription->current_period_end->toIso8601String(),
                    'daysUntilRenewal' => $company->activeSubscription->daysUntilRenewal(),
                ] : null,
            ]);
        }

        return $data;
    }
}
