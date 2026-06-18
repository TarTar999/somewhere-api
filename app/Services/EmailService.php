<?php

namespace App\Services;

use App\Mail\Alert\DocumentExpiringMail;
use App\Mail\Transactional\DocumentGeneratedMail;
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
        try {
            // TODO: Implement PaymentConfirmationMail
            Log::info('Payment confirmation email would be sent', [
                'user_id' => $user->id,
                'payment' => $paymentDetails,
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
        try {
            // TODO: Implement KycStatusMail
            Log::info('KYC status email would be sent', [
                'user_id' => $user->id,
                'status' => $status,
                'reason' => $reason,
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
    public function sendSubscriptionExpiringAlert($company, int $daysUntilExpiration): bool
    {
        try {
            // Get company admins
            $admins = $company->users()->wherePivot('role', 'admin')->get();

            foreach ($admins as $admin) {
                // TODO: Implement SubscriptionExpiringMail
                Log::info('Subscription expiring email would be sent', [
                    'company_id' => $company->id,
                    'admin_id' => $admin->id,
                    'days_until_expiration' => $daysUntilExpiration,
                ]);
            }

            return true;
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
        try {
            // TODO: Implement SuspiciousActivityMail
            Log::info('Suspicious activity email would be sent', [
                'user_id' => $user->id,
                'activity' => $activityDetails,
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
