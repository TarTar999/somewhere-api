<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\ProofOfLocation;
use App\Services\CompanySubscriptionService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected CompanySubscriptionService $subscriptionService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $subscription = $company->activeSubscription;
        $memberIds = $company->members()->pluck('users.id');

        // Get stats
        $documentsThisMonth = $subscription ? $company->documents()
            ->whereBetween('created_at', [
                $subscription->current_period_start,
                $subscription->current_period_end,
            ])
            ->count() : 0;

        $totalAddresses = Address::whereIn('user_id', $memberIds)->count();

        $recentDocuments = ProofOfLocation::where('company_id', $company->id)
            ->with(['user:id,first_name,last_name', 'address:id,sw_address'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'documentNumber' => $doc->document_number,
                'documentType' => $doc->document_type,
                'documentTypeLabel' => $doc->document_type_label,
                'status' => $doc->status,
                'createdAt' => $doc->created_at->toIso8601String(),
                'address' => $doc->address?->sw_address,
                'createdBy' => $doc->user?->full_name,
            ]);

        $recentMembers = $company->members()
            ->orderByPivot('joined_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
                'email' => $member->email,
                'role' => $member->pivot->role,
                'joinedAt' => $member->pivot->joined_at?->toIso8601String(),
            ]);

        return Inertia::render('company/dashboard', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'status' => $company->status,
            ],
            'userRole' => $user->getCompanyRole($company),
            'stats' => [
                'totalMembers' => $company->getMemberCount(),
                'maxMembers' => $subscription?->max_members ?? 0,
                'totalAddresses' => $totalAddresses,
                'documentsThisMonth' => $documentsThisMonth,
                'documentsLimit' => $subscription?->documents_per_month ?? 0,
                'documentsRemaining' => $company->getRemainingDocuments(),
            ],
            'subscription' => $subscription ? [
                'plan' => $subscription->plan_code,
                'planName' => config("company.plans.{$subscription->plan_code}.name"),
                'status' => $subscription->status,
                'periodEnd' => $subscription->current_period_end->toIso8601String(),
                'daysUntilRenewal' => $subscription->daysUntilRenewal(),
            ] : null,
            'recentDocuments' => $recentDocuments,
            'recentMembers' => $recentMembers,
        ]);
    }
}
