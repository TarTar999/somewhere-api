<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ProofOfLocation;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class NotificationService
{
    protected SmsService $smsService;
    protected PushNotificationService $pushService;

    public function __construct(SmsService $smsService, PushNotificationService $pushService)
    {
        $this->smsService = $smsService;
        $this->pushService = $pushService;
    }

    /**
     * Créer une notification
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $body,
        array $options = []
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'category' => $options['category'] ?? $this->getCategoryFromType($type),
            'title' => $title,
            'body' => $body,
            'data' => $options['data'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'priority' => $options['priority'] ?? Notification::PRIORITY_NORMAL,
            'action_url' => $options['action_url'] ?? null,
            'action_type' => $options['action_type'] ?? null,
        ]);

        Log::info('Notification created', [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
        ]);

        // Envoyer push notification (par défaut activé)
        if ($options['send_push'] ?? true) {
            $this->sendPushNotification($user, $notification);
        }

        // Envoyer par SMS si demandé
        if ($options['send_sms'] ?? false) {
            $this->sendSmsNotification($user, $title, $body);
        }

        return $notification;
    }

    /**
     * Notifier pour un document qui expire bientôt
     */
    public function notifyDocumentExpiring(ProofOfLocation $document, int $daysUntilExpiry): Notification
    {
        $title = 'Document expire bientôt';
        $body = "Votre {$document->document_type_label} expire dans {$daysUntilExpiry} jours. Pensez à le renouveler.";

        return $this->create(
            $document->user,
            Notification::TYPE_DOCUMENT_EXPIRING,
            $title,
            $body,
            [
                'category' => Notification::CATEGORY_DOCUMENT,
                'reference_type' => ProofOfLocation::class,
                'reference_id' => $document->id,
                'priority' => $daysUntilExpiry <= 7 ? Notification::PRIORITY_HIGH : Notification::PRIORITY_NORMAL,
                'action_type' => Notification::ACTION_RENEW_DOCUMENT,
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'address_id' => $document->address_id,
                    'expires_at' => $document->expires_at->toIso8601String(),
                    'days_until_expiry' => $daysUntilExpiry,
                ],
            ]
        );
    }

    /**
     * Notifier pour un document expiré
     */
    public function notifyDocumentExpired(ProofOfLocation $document): Notification
    {
        $title = 'Document expiré';
        $body = "Votre {$document->document_type_label} a expiré. Veuillez le renouveler pour continuer à l'utiliser.";

        return $this->create(
            $document->user,
            Notification::TYPE_DOCUMENT_EXPIRED,
            $title,
            $body,
            [
                'category' => Notification::CATEGORY_DOCUMENT,
                'reference_type' => ProofOfLocation::class,
                'reference_id' => $document->id,
                'priority' => Notification::PRIORITY_HIGH,
                'action_type' => Notification::ACTION_RENEW_DOCUMENT,
                'data' => [
                    'document_id' => $document->id,
                    'document_type' => $document->document_type,
                    'address_id' => $document->address_id,
                    'expired_at' => $document->expires_at->toIso8601String(),
                ],
            ]
        );
    }

    /**
     * Notifier pour un changement de statut KYC
     */
    public function notifyKycStatusChange(KycVerification $kyc, string $oldStatus): Notification
    {
        $statusMessages = [
            'approved' => [
                'title' => 'KYC Approuvé',
                'body' => 'Félicitations ! Votre vérification d\'identité a été approuvée.',
                'priority' => Notification::PRIORITY_HIGH,
            ],
            'rejected' => [
                'title' => 'KYC Rejeté',
                'body' => "Votre vérification d'identité a été rejetée. Raison : {$kyc->rejection_reason}",
                'priority' => Notification::PRIORITY_URGENT,
            ],
            'in_review' => [
                'title' => 'KYC en cours de vérification',
                'body' => 'Votre dossier est en cours d\'examen par notre équipe.',
                'priority' => Notification::PRIORITY_NORMAL,
            ],
        ];

        $message = $statusMessages[$kyc->status] ?? [
            'title' => 'Mise à jour KYC',
            'body' => "Le statut de votre vérification a changé : {$kyc->status}",
            'priority' => Notification::PRIORITY_NORMAL,
        ];

        return $this->create(
            $kyc->user,
            Notification::TYPE_KYC_STATUS,
            $message['title'],
            $message['body'],
            [
                'category' => Notification::CATEGORY_KYC,
                'reference_type' => KycVerification::class,
                'reference_id' => $kyc->id,
                'priority' => $message['priority'],
                'action_type' => Notification::ACTION_COMPLETE_KYC,
                'data' => [
                    'kyc_id' => $kyc->id,
                    'old_status' => $oldStatus,
                    'new_status' => $kyc->status,
                ],
            ]
        );
    }

    /**
     * Envoyer un message d'engagement à un utilisateur
     */
    public function sendEngagementMessage(
        User $user,
        string $title,
        string $body,
        array $options = []
    ): Notification {
        return $this->create(
            $user,
            Notification::TYPE_ENGAGEMENT,
            $title,
            $body,
            array_merge([
                'category' => Notification::CATEGORY_ENGAGEMENT,
                'priority' => Notification::PRIORITY_NORMAL,
            ], $options)
        );
    }

    /**
     * Envoyer un message d'engagement à plusieurs utilisateurs
     */
    public function broadcastEngagementMessage(
        string $title,
        string $body,
        ?Collection $users = null,
        array $options = []
    ): int {
        $users = $users ?? User::all();
        $count = 0;

        foreach ($users as $user) {
            $this->sendEngagementMessage($user, $title, $body, $options);
            $count++;
        }

        Log::info('Broadcast engagement message sent', [
            'title' => $title,
            'recipients_count' => $count,
        ]);

        return $count;
    }

    /**
     * Envoyer une notification système
     */
    public function sendSystemNotification(
        User $user,
        string $title,
        string $body,
        array $options = []
    ): Notification {
        return $this->create(
            $user,
            Notification::TYPE_SYSTEM,
            $title,
            $body,
            array_merge([
                'category' => Notification::CATEGORY_SYSTEM,
            ], $options)
        );
    }

    /**
     * Notifier pour un paiement
     */
    public function notifyPaymentStatus(
        User $user,
        string $status,
        array $paymentData
    ): Notification {
        $messages = [
            'paid' => [
                'title' => 'Paiement réussi',
                'body' => "Votre paiement de {$paymentData['amount']} XAF a été effectué avec succès.",
                'priority' => Notification::PRIORITY_NORMAL,
            ],
            'failed' => [
                'title' => 'Échec du paiement',
                'body' => "Votre paiement de {$paymentData['amount']} XAF a échoué. Veuillez réessayer.",
                'priority' => Notification::PRIORITY_HIGH,
            ],
            'expired' => [
                'title' => 'Paiement expiré',
                'body' => "Votre demande de paiement a expiré. Veuillez initier un nouveau paiement.",
                'priority' => Notification::PRIORITY_NORMAL,
            ],
        ];

        $message = $messages[$status] ?? [
            'title' => 'Mise à jour paiement',
            'body' => "Le statut de votre paiement a changé : {$status}",
            'priority' => Notification::PRIORITY_NORMAL,
        ];

        return $this->create(
            $user,
            Notification::TYPE_PAYMENT,
            $message['title'],
            $message['body'],
            [
                'category' => Notification::CATEGORY_PAYMENT,
                'priority' => $message['priority'],
                'data' => $paymentData,
            ]
        );
    }

    /**
     * Marquer plusieurs notifications comme lues
     */
    public function markAsRead(User $user, array $notificationIds = []): int
    {
        $query = Notification::where('user_id', $user->id)->whereNull('read_at');

        if (!empty($notificationIds)) {
            $query->whereIn('id', $notificationIds);
        }

        return $query->update(['read_at' => now()]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return $this->markAsRead($user);
    }

    /**
     * Supprimer les anciennes notifications
     */
    public function deleteOldNotifications(int $daysOld = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))->delete();
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->unread()->count();
    }

    /**
     * Envoyer une push notification
     */
    protected function sendPushNotification(User $user, Notification $notification): array
    {
        return $this->pushService->sendToUser($user, $notification);
    }

    /**
     * Envoyer une notification par SMS
     */
    protected function sendSmsNotification(User $user, string $title, string $body): bool
    {
        if (empty($user->phone)) {
            return false;
        }

        $message = "{$title}: {$body}";

        // Limiter la longueur du SMS
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }

        return $this->smsService->send($user->phone, $message);
    }

    /**
     * Déterminer la catégorie à partir du type
     */
    protected function getCategoryFromType(string $type): string
    {
        return match ($type) {
            Notification::TYPE_DOCUMENT_EXPIRING,
            Notification::TYPE_DOCUMENT_EXPIRED => Notification::CATEGORY_DOCUMENT,
            Notification::TYPE_KYC_STATUS,
            Notification::TYPE_KYC_EXPIRING => Notification::CATEGORY_KYC,
            Notification::TYPE_ENGAGEMENT => Notification::CATEGORY_ENGAGEMENT,
            Notification::TYPE_PAYMENT => Notification::CATEGORY_PAYMENT,
            default => Notification::CATEGORY_SYSTEM,
        };
    }
}
