<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\ProofOfLocation;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyExpiringDocuments extends Command
{
    protected $signature = 'notifications:expiring-documents
                            {--days=7 : Nombre de jours avant expiration}
                            {--sms : Envoyer aussi par SMS}';

    protected $description = 'Notifier les utilisateurs dont les documents vont bientôt expirer';

    public function __construct(
        protected NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $sendSms = $this->option('sms');

        $this->info("Recherche des documents expirant dans les {$days} prochains jours...");

        // Trouver les documents qui expirent bientôt
        $expiringDocuments = ProofOfLocation::where('status', 'active')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->with('user')
            ->get();

        $notifiedCount = 0;
        $skippedCount = 0;

        foreach ($expiringDocuments as $document) {
            // Vérifier si on n'a pas déjà notifié pour ce document récemment
            $existingNotification = Notification::where('user_id', $document->user_id)
                ->where('reference_type', ProofOfLocation::class)
                ->where('reference_id', $document->id)
                ->where('type', Notification::TYPE_DOCUMENT_EXPIRING)
                ->where('created_at', '>=', now()->subDays(1)) // Pas plus d'une notification par jour
                ->exists();

            if ($existingNotification) {
                $skippedCount++;
                continue;
            }

            $daysUntilExpiry = (int) now()->diffInDays($document->expires_at, false);

            if ($daysUntilExpiry > 0) {
                $this->notificationService->notifyDocumentExpiring($document, $daysUntilExpiry);
                $notifiedCount++;

                $this->line("  - Notifié: {$document->user->full_name} ({$document->document_type_label}) - expire dans {$daysUntilExpiry} jours");
            }
        }

        $this->info("Notifications envoyées: {$notifiedCount}");
        $this->info("Notifications ignorées (déjà envoyées): {$skippedCount}");

        // Notifier aussi les documents expirés non encore notifiés
        $this->notifyExpiredDocuments();

        return Command::SUCCESS;
    }

    protected function notifyExpiredDocuments(): void
    {
        $expiredDocuments = ProofOfLocation::where('status', 'expired')
            ->where('expires_at', '>=', now()->subDays(7)) // Expirés dans les 7 derniers jours
            ->with('user')
            ->get();

        $notifiedCount = 0;

        foreach ($expiredDocuments as $document) {
            // Vérifier si on n'a pas déjà notifié pour l'expiration
            $existingNotification = Notification::where('user_id', $document->user_id)
                ->where('reference_type', ProofOfLocation::class)
                ->where('reference_id', $document->id)
                ->where('type', Notification::TYPE_DOCUMENT_EXPIRED)
                ->exists();

            if ($existingNotification) {
                continue;
            }

            $this->notificationService->notifyDocumentExpired($document);
            $notifiedCount++;

            $this->line("  - Notifié expiration: {$document->user->full_name} ({$document->document_type_label})");
        }

        if ($notifiedCount > 0) {
            $this->info("Notifications d'expiration envoyées: {$notifiedCount}");
        }
    }
}
