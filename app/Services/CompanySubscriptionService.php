<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\CompanySubscription;
use Illuminate\Support\Str;

class CompanySubscriptionService
{
    public function __construct(
        protected FapshiService $fapshiService
    ) {}

    public function subscribe(Company $company, string $planCode, ?string $phone = null): CompanyPayment
    {
        $plan = CompanySubscription::getPlan($planCode);
        if (!$plan) {
            throw new \Exception('Invalid plan');
        }

        // Check for existing active subscription
        if ($company->hasActiveSubscription()) {
            throw new \Exception('Company already has an active subscription');
        }

        // Create subscription (pending until payment)
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'plan_code' => $planCode,
            'price' => $plan['price'],
            'max_members' => $plan['max_members'],
            'documents_per_month' => $plan['documents_per_month'],
            'status' => CompanySubscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Create payment
        $transactionId = 'CSUB-' . strtoupper(Str::random(12));

        $payment = CompanyPayment::create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'transaction_id' => $transactionId,
            'amount' => $plan['price'],
            'currency' => 'XAF',
            'status' => CompanyPayment::STATUS_PENDING,
            'phone' => $phone,
        ]);

        // Initiate Fapshi payment
        try {
            $fapshiResponse = $this->fapshiService->initiatePay(
                $plan['price'],
                $payment->transaction_id,
                "Abonnement {$plan['name']} - {$company->name}",
                $phone
            );

            $payment->update([
                'payment_link' => $fapshiResponse['link'] ?? null,
                'external_id' => $fapshiResponse['transId'] ?? null,
                'fapshi_response' => $fapshiResponse,
            ]);
        } catch (\Exception $e) {
            $payment->markAsFailed($e->getMessage());
            $subscription->delete();
            throw $e;
        }

        return $payment;
    }

    public function renew(Company $company, ?string $phone = null): CompanyPayment
    {
        $subscription = $company->activeSubscription;
        if (!$subscription) {
            throw new \Exception('No active subscription to renew');
        }

        $plan = CompanySubscription::getPlan($subscription->plan_code);
        if (!$plan) {
            throw new \Exception('Invalid plan');
        }

        $transactionId = 'CREN-' . strtoupper(Str::random(12));

        $payment = CompanyPayment::create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'transaction_id' => $transactionId,
            'amount' => $plan['price'],
            'currency' => 'XAF',
            'status' => CompanyPayment::STATUS_PENDING,
            'phone' => $phone,
        ]);

        try {
            $fapshiResponse = $this->fapshiService->initiatePay(
                $plan['price'],
                $payment->transaction_id,
                "Renouvellement {$plan['name']} - {$company->name}",
                $phone
            );

            $payment->update([
                'payment_link' => $fapshiResponse['link'] ?? null,
                'external_id' => $fapshiResponse['transId'] ?? null,
                'fapshi_response' => $fapshiResponse,
            ]);
        } catch (\Exception $e) {
            $payment->markAsFailed($e->getMessage());
            throw $e;
        }

        return $payment;
    }

    public function processPaymentSuccess(CompanyPayment $payment): void
    {
        $payment->markAsSuccessful();

        $subscription = $payment->subscription;
        if (!$subscription) {
            return;
        }

        // If this is a renewal, extend the period
        if ($subscription->status === CompanySubscription::STATUS_ACTIVE) {
            $subscription->update([
                'current_period_end' => $subscription->current_period_end->addMonth(),
            ]);
        } else {
            // Activate the subscription
            $subscription->update([
                'status' => CompanySubscription::STATUS_ACTIVE,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        // Activate the company if pending
        $company = $payment->company;
        if ($company->isPending()) {
            $company->activate();
        }
    }

    public function cancel(Company $company): void
    {
        $subscription = $company->activeSubscription;
        if (!$subscription) {
            throw new \Exception('No active subscription');
        }

        $subscription->cancel();
    }

    public function changePlan(Company $company, string $newPlanCode, ?string $phone = null): CompanyPayment
    {
        $subscription = $company->activeSubscription;
        if (!$subscription) {
            throw new \Exception('No active subscription');
        }

        $newPlan = CompanySubscription::getPlan($newPlanCode);
        if (!$newPlan) {
            throw new \Exception('Invalid plan');
        }

        // If downgrading, check member limit
        if ($newPlan['max_members'] < $company->getMemberCount()) {
            throw new \Exception('New plan does not support current number of members');
        }

        // Cancel current and create new subscription
        $subscription->cancel();

        return $this->subscribe($company, $newPlanCode, $phone);
    }

    public function checkExpiredSubscriptions(): int
    {
        $expired = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
            ->where('current_period_end', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->expire();
        }

        return $expired->count();
    }

    public function sendRenewalReminders(int $daysBeforeExpiry = 7): int
    {
        $subscriptions = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
            ->whereBetween('current_period_end', [
                now(),
                now()->addDays($daysBeforeExpiry),
            ])
            ->get();

        foreach ($subscriptions as $subscription) {
            // Send notification to company admins
            $company = $subscription->company;
            foreach ($company->admins as $admin) {
                // TODO: Send email/notification
            }
        }

        return $subscriptions->count();
    }

    public function getUsageStats(Company $company): array
    {
        $subscription = $company->activeSubscription;
        if (!$subscription) {
            return [
                'hasSubscription' => false,
            ];
        }

        $documentsUsed = $company->documents()
            ->whereBetween('created_at', [
                $subscription->current_period_start,
                $subscription->current_period_end,
            ])
            ->count();

        return [
            'hasSubscription' => true,
            'plan' => $subscription->plan_code,
            'status' => $subscription->status,
            'periodStart' => $subscription->current_period_start->toIso8601String(),
            'periodEnd' => $subscription->current_period_end->toIso8601String(),
            'daysUntilRenewal' => $subscription->daysUntilRenewal(),
            'members' => [
                'used' => $company->getMemberCount(),
                'limit' => $subscription->max_members,
            ],
            'documents' => [
                'used' => $documentsUsed,
                'limit' => $subscription->documents_per_month,
                'remaining' => max(0, $subscription->documents_per_month - $documentsUsed),
            ],
        ];
    }
}
