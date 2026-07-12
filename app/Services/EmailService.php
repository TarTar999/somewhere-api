<?php

namespace App\Services;

use App\Mail\Alert\DocumentExpiringMail;
use App\Mail\Alert\SubscriptionExpiringMail;
use App\Mail\Alert\SuspiciousActivityMail;
use App\Mail\Transactional\DocumentGeneratedMail;
use App\Mail\Transactional\KycStatusMail;
use App\Mail\Transactional\PaymentConfirmationMail;
use App\Models\Company;
use App\Models\ProofOfLocation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send document generated notification.
     */
    public function sendDocumentGenerated(User $user, ProofOfLocation $document): bool
    {
        if (!config('documents.notifications.send_verification_notification', true)) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new DocumentGeneratedMail($user, $document));

            Log::info('Document generated email sent', [
                'user_id' => $user->id,
                'document_id' => $document->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send document generated email', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send document expiring alert.
     */
    public function sendDocumentExpiringAlert(User $user, ProofOfLocation $document): bool
    {
        $daysUntilExpiration = now()->diffInDays($document->expires_at, false);

        if ($daysUntilExpiration < 0 || $daysUntilExpiration > config('documents.notifications.expiration_warning_days', 7)) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new DocumentExpiringMail($user, $document, $daysUntilExpiration));

            Log::info('Document expiring email sent', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'days_until_expiration' => $daysUntilExpiration,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send document expiring email', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send payment confirmation email.
     */
    public function sendPaymentConfirmation(User $user, array $paymentDetails): bool
    {
        if (!$user->email) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new PaymentConfirmationMail($user, $paymentDetails));

            Log::info('Payment confirmation email sent', [
                'user_id' => $user->id,
                'transaction_id' => $paymentDetails['transaction_id'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send KYC status update email.
     */
    public function sendKycStatusUpdate(User $user, string $status, ?string $reason = null): bool
    {
        if (!$user->email) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new KycStatusMail($user, $status, $reason));

            Log::info('KYC status email sent', [
                'user_id' => $user->id,
                'status' => $status,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send KYC status email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send subscription expiring alert to company admins.
     */
    public function sendSubscriptionExpiringAlert(Company $company, int $daysUntilExpiration): bool
    {
        try {
            // Get company admins
            $admins = $company->users()->wherePivot('role', 'admin')->get();
            $sentCount = 0;

            foreach ($admins as $admin) {
                if (!$admin->email) {
                    continue;
                }

                Mail::to($admin->email)->send(new SubscriptionExpiringMail($admin, $company, $daysUntilExpiration));
                $sentCount++;

                Log::info('Subscription expiring email sent', [
                    'company_id' => $company->id,
                    'admin_id' => $admin->id,
                    'days_until_expiration' => $daysUntilExpiration,
                ]);
            }

            return $sentCount > 0;
        } catch (\Exception $e) {
            Log::error('Failed to send subscription expiring email', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send suspicious activity alert.
     */
    public function sendSuspiciousActivityAlert(User $user, array $activityDetails): bool
    {
        if (!$user->email) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new SuspiciousActivityMail($user, $activityDetails));

            Log::info('Suspicious activity email sent', [
                'user_id' => $user->id,
                'activity_type' => $activityDetails['type'] ?? 'unknown',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send suspicious activity email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check and send expiration alerts for all documents.
     * This should be run daily via a scheduled command.
     */
    public function processExpirationAlerts(): array
    {
        $warningDays = config('documents.notifications.expiration_warning_days', 7);
        $sentCount = 0;
        $failedCount = 0;

        $expiringDocuments = ProofOfLocation::query()
            ->where('status', 'approved')
            ->whereDate('expires_at', '<=', now()->addDays($warningDays))
            ->whereDate('expires_at', '>=', now())
            ->whereNull('expiration_alert_sent_at')
            ->with('user')
            ->get();

        foreach ($expiringDocuments as $document) {
            if ($this->sendDocumentExpiringAlert($document->user, $document)) {
                $document->update(['expiration_alert_sent_at' => now()]);
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        return [
            'processed' => $expiringDocuments->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
        ];
    }
}
