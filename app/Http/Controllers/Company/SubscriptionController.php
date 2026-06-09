<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\CompanySubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected CompanySubscriptionService $subscriptionService
    ) {}

    public function show(): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;
        $subscription = $company->activeSubscription;

        return Inertia::render('company/subscription/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'status' => $company->status,
            ],
            'userRole' => $user->getCompanyRole($company),
            'hasSubscription' => $subscription !== null,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan' => $subscription->plan_code,
                'planName' => config("company.plans.{$subscription->plan_code}.name"),
                'price' => $subscription->price,
                'priceFormatted' => number_format($subscription->price) . ' XAF',
                'status' => $subscription->status,
                'maxMembers' => $subscription->max_members,
                'documentsPerMonth' => $subscription->documents_per_month,
                'periodStart' => $subscription->current_period_start->format('d/m/Y'),
                'periodEnd' => $subscription->current_period_end->format('d/m/Y'),
                'daysUntilRenewal' => $subscription->daysUntilRenewal(),
                'isCancelled' => $subscription->isCancelled(),
            ] : null,
            'usage' => $this->subscriptionService->getUsageStats($company),
            'plans' => $this->getFormattedPlans(),
        ]);
    }

    public function plans(): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        return Inertia::render('company/subscription/plans', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'status' => $company->status,
            ],
            'userRole' => $user->getCompanyRole($company),
            'plans' => $this->getFormattedPlans(),
            'currentPlan' => $company->activeSubscription?->plan_code,
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate([
            'planCode' => 'required|in:basic,professional,enterprise',
            'phone' => 'nullable|string|max:20',
        ]);

        $company = auth()->user()->currentCompany;

        try {
            $payment = $this->subscriptionService->subscribe(
                $company,
                $request->planCode,
                $request->phone
            );

            if ($payment->payment_link) {
                return Inertia::location($payment->payment_link);
            }

            return redirect()->route('company.subscription.show')
                ->with('success', 'Abonnement en cours de traitement');
        } catch (\Exception $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function renew(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
        ]);

        $company = auth()->user()->currentCompany;

        try {
            $payment = $this->subscriptionService->renew($company, $request->phone);

            if ($payment->payment_link) {
                return Inertia::location($payment->payment_link);
            }

            return redirect()->route('company.subscription.show')
                ->with('success', 'Renouvellement en cours de traitement');
        } catch (\Exception $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function cancel(): RedirectResponse
    {
        $company = auth()->user()->currentCompany;

        try {
            $this->subscriptionService->cancel($company);

            return redirect()->route('company.subscription.show')
                ->with('success', 'Abonnement annulé');
        } catch (\Exception $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }
    }

    public function invoices(): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        $payments = $company->payments()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'transactionId' => $payment->transaction_id,
                'amount' => $payment->amount,
                'amountFormatted' => number_format($payment->amount) . ' XAF',
                'status' => $payment->status,
                'statusLabel' => match ($payment->status) {
                    'pending' => 'En attente',
                    'successful' => 'Payé',
                    'failed' => 'Échoué',
                    'expired' => 'Expiré',
                    default => $payment->status,
                },
                'paidAt' => $payment->paid_at?->format('d/m/Y H:i'),
                'createdAt' => $payment->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('company/subscription/invoices', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'status' => $company->status,
            ],
            'userRole' => $user->getCompanyRole($company),
            'payments' => $payments,
        ]);
    }

    protected function getFormattedPlans(): array
    {
        return collect(config('company.plans', []))->map(function ($plan, $code) {
            return [
                'code' => $code,
                'name' => $plan['name'],
                'price' => $plan['price'],
                'priceFormatted' => number_format($plan['price']) . ' XAF/mois',
                'maxMembers' => $plan['max_members'],
                'documentsPerMonth' => $plan['documents_per_month'],
                'features' => $plan['features'],
            ];
        })->values()->toArray();
    }
}
