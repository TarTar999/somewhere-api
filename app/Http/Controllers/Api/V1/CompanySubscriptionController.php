<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\CompanySubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySubscriptionController extends Controller
{
    public function __construct(
        protected CompanySubscriptionService $subscriptionService
    ) {}

    public function show(): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        $subscription = $company->activeSubscription;

        if (!$subscription) {
            return $this->success([
                'hasSubscription' => false,
                'plans' => $this->getFormattedPlans(),
            ]);
        }

        return $this->success([
            'hasSubscription' => true,
            'subscription' => [
                'id' => $subscription->id,
                'plan' => $subscription->plan_code,
                'planName' => config("company.plans.{$subscription->plan_code}.name"),
                'price' => $subscription->price,
                'priceFormatted' => number_format($subscription->price) . ' XAF',
                'status' => $subscription->status,
                'maxMembers' => $subscription->max_members,
                'documentsPerMonth' => $subscription->documents_per_month,
                'periodStart' => $subscription->current_period_start->toIso8601String(),
                'periodEnd' => $subscription->current_period_end->toIso8601String(),
                'daysUntilRenewal' => $subscription->daysUntilRenewal(),
                'cancelledAt' => $subscription->cancelled_at?->toIso8601String(),
            ],
            'usage' => $this->subscriptionService->getUsageStats($company),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can manage subscriptions', 403);
        }

        $request->validate([
            'planCode' => 'required|string|in:basic,professional,enterprise',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $payment = $this->subscriptionService->subscribe(
                $company,
                $request->planCode,
                $request->phone
            );

            return $this->success([
                'paymentId' => $payment->id,
                'transactionId' => $payment->transaction_id,
                'paymentLink' => $payment->payment_link,
                'amount' => $payment->amount,
                'amountFormatted' => number_format($payment->amount) . ' XAF',
            ], 'Subscription initiated', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function renew(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can manage subscriptions', 403);
        }

        $request->validate([
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $payment = $this->subscriptionService->renew($company, $request->phone);

            return $this->success([
                'paymentId' => $payment->id,
                'transactionId' => $payment->transaction_id,
                'paymentLink' => $payment->payment_link,
                'amount' => $payment->amount,
                'amountFormatted' => number_format($payment->amount) . ' XAF',
            ], 'Renewal initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancel(): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can manage subscriptions', 403);
        }

        try {
            $this->subscriptionService->cancel($company);

            return $this->success(null, 'Subscription cancelled');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function changePlan(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can manage subscriptions', 403);
        }

        $request->validate([
            'planCode' => 'required|string|in:basic,professional,enterprise',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $payment = $this->subscriptionService->changePlan(
                $company,
                $request->planCode,
                $request->phone
            );

            return $this->success([
                'paymentId' => $payment->id,
                'transactionId' => $payment->transaction_id,
                'paymentLink' => $payment->payment_link,
                'amount' => $payment->amount,
            ], 'Plan change initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function payments(): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        $payments = $company->payments()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'transactionId' => $payment->transaction_id,
                'amount' => $payment->amount,
                'amountFormatted' => number_format($payment->amount) . ' XAF',
                'status' => $payment->status,
                'paidAt' => $payment->paid_at?->toIso8601String(),
                'createdAt' => $payment->created_at->toIso8601String(),
            ]);

        return $this->success($payments);
    }

    protected function getFormattedPlans(): array
    {
        return collect(config('company.plans', []))->map(function ($plan, $code) {
            return [
                'code' => $code,
                'name' => $plan['name'],
                'price' => $plan['price'],
                'priceFormatted' => number_format($plan['price']) . ' XAF',
                'maxMembers' => $plan['max_members'],
                'documentsPerMonth' => $plan['documents_per_month'],
                'features' => $plan['features'],
            ];
        })->values()->toArray();
    }
}
