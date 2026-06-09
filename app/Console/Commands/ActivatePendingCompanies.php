<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Console\Command;

class ActivatePendingCompanies extends Command
{
    protected $signature = 'company:activate-pending {--company= : Specific company ID to activate}';

    protected $description = 'Activate pending companies and create trial subscriptions if missing';

    public function handle(): int
    {
        $companyId = $this->option('company');

        $query = Company::where('status', Company::STATUS_PENDING);

        if ($companyId) {
            $query->where('id', $companyId);
        }

        $pendingCompanies = $query->get();

        if ($pendingCompanies->isEmpty()) {
            $this->info('No pending companies found.');
            return self::SUCCESS;
        }

        $this->info("Found {$pendingCompanies->count()} pending company(ies).");

        foreach ($pendingCompanies as $company) {
            $this->activateCompany($company);
        }

        $this->info('Done!');
        return self::SUCCESS;
    }

    protected function activateCompany(Company $company): void
    {
        $this->line("Activating company: {$company->name} (ID: {$company->id})");

        // Activate the company
        $company->update([
            'status' => Company::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        // Create trial subscription if none exists
        if (!$company->activeSubscription) {
            $this->createTrialSubscription($company);
            $this->info("  - Created trial subscription");
        } else {
            $this->info("  - Active subscription already exists");
        }

        $this->info("  - Company activated successfully");
    }

    protected function createTrialSubscription(Company $company): void
    {
        $trialDays = config('company.trial_days', 14);
        $starterPlan = config('company.plans.starter');

        CompanySubscription::create([
            'company_id' => $company->id,
            'plan_code' => 'starter',
            'price' => 0,
            'max_members' => $starterPlan['max_members'] ?? 3,
            'documents_per_month' => $starterPlan['documents_per_month'] ?? 25,
            'status' => CompanySubscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($trialDays),
        ]);
    }
}
