<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private $messaging;
    private bool $isConfigured = false;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Initialiser Firebase
     */
    private function initializeFirebase(): void
    {
        try {
            $credentialsPath = config('firebase.credentials_path');
            $credentialsBase64 = config('firebase.credentials_base64');

            if ($credentialsPath && file_exists($credentialsPath)) {
                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->messaging = $factory->createMessaging();
                $this->isConfigured = true;
            } elseif ($credentialsBase64) {
                $credentials = json_decode(base64_decode($credentialsBase64), true);
                $factory = (new Factory)->withServiceAccount($credentials);
                $this->messaging = $factory->createMessaging();
                $this->isConfigured = true;
            } else {
                Log::warning('Firebase credentials not configured');
            }
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si Firebase est configuré
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Envoyer une push notification à un utilisateur
     */
    public function sendToUser(User $user, Notification $notification): array
    {
        $tokens = $user->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            Log::info('No active device tokens for user', ['user_id' => $user->id]);
            return ['success' => false, 'reason' => 'no_tokens', 'sent' => 0];
        }

        return $this->sendToTokens($tokens, $notification);
    }

    /**
     * Envoyer une push notification à plusieurs tokens
     */
    public function sendToTokens(array $tokens, Notification $notification): array
    {
        if (!$this->isConfigured()) {
            Log::warning('Firebase not configured. Push notification not sent.');
            return ['success' => false, 'reason' => 'not_configured', 'sent' => 0];
        }

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'invalid_tokens' => [],
        ];

        foreach ($tokens as $token) {
            try {
                $message = $this->buildMessage($token, $notification);
                $this->messaging->send($message);
                $results['sent']++;

                // Mettre à jour last_used_at
                DeviceToken::where('token', $token)->update(['last_used_at' => now()]);

            } catch (NotFound $e) {
                // Token invalide - le désactiver
                $results['failed']++;
                $results['invalid_tokens'][] = $token;
                DeviceToken::where('token', $token)->update(['is_active' => false]);
                Log::warning("FCM token invalide désactivé: {$token}");

            } catch (\Exception $e) {
                $results['failed']++;
                Log::error("Erreur envoi FCM: " . $e->getMessage());
            }
        }

        // Marquer la notification comme envoyée
        if ($results['sent'] > 0) {
            $notification->markAsSent();
        }

        $results['success'] = $results['sent'] > 0;
        return $results;
    }

    /**
     * Construire le message FCM
     */
    private function buildMessage(string $token, Notification $notification): CloudMessage
    {
        $message = CloudMessage::withTarget('token', $token);

        // Notification visible
        $message = $message->withNotification(
            FcmNotification::create($notification->title, $notification->body)
        );

        // Données personnalisées
        $payload = [
            'type' => $notification->type,
            'category' => $notification->category,
            'action_type' => $notification->action_type ?? '',
            'action_url' => $notification->action_url ?? '',
            'notification_id' => (string) $notification->id,
            'priority' => $notification->priority,
        ];

        // Ajouter les données additionnelles
        if ($notification->data) {
            $payload['data'] = json_encode($notification->data);
        }

        $message = $message->withData(array_filter($payload));

        // Configuration Android
        $androidConfig = AndroidConfig::fromArray([
            'priority' => $this->getAndroidPriority($notification->priority),
            'notification' => [
                'channel_id' => $this->getChannelId($notification->category),
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ]);
        $message = $message->withAndroidConfig($androidConfig);

        // Configuration iOS
        $apnsConfig = ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => $this->getApnsPriority($notification->priority),
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ]);
        $message = $message->withApnsConfig($apnsConfig);

        return $message;
    }

    /**
     * Obtenir la priorité Android
     */
    private function getAndroidPriority(string $priority): string
    {
        return in_array($priority, ['high', 'urgent']) ? 'high' : 'normal';
    }

    /**
     * Obtenir la priorité APNs
     */
    private function getApnsPriority(string $priority): string
    {
        return in_array($priority, ['high', 'urgent']) ? '10' : '5';
    }

    /**
     * Obtenir le channel ID Android selon la catégorie
     */
    private function getChannelId(string $category): string
    {
        return match ($category) {
            Notification::CATEGORY_DOCUMENT => 'documents',
            Notification::CATEGORY_KYC => 'documents',
            Notification::CATEGORY_PAYMENT => 'payments',
            Notification::CATEGORY_ENGAGEMENT => 'engagement',
            default => 'general',
        };
    }

    /**
     * Envoyer à tous les utilisateurs (broadcast)
     */
    public function sendToAll(array $notificationData): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'reason' => 'not_configured', 'sent' => 0];
        }

        $tokens = DeviceToken::where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'reason' => 'no_tokens', 'sent' => 0];
        }

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'invalid_tokens' => [],
        ];

        foreach ($tokens as $token) {
            try {
                $message = $this->buildBroadcastMessage($token, $notificationData);
                $this->messaging->send($message);
                $results['sent']++;
            } catch (NotFound $e) {
                $results['failed']++;
                $results['invalid_tokens'][] = $token;
                DeviceToken::where('token', $token)->update(['is_active' => false]);
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error("Erreur envoi FCM broadcast: " . $e->getMessage());
            }
        }

        $results['success'] = $results['sent'] > 0;
        return $results;
    }

    /**
     * Construire un message pour broadcast
     */
    private function buildBroadcastMessage(string $token, array $data): CloudMessage
    {
        $message = CloudMessage::withTarget('token', $token);

        if (isset($data['title']) && isset($data['body'])) {
            $message = $message->withNotification(
                FcmNotification::create($data['title'], $data['body'])
            );
        }

        $payload = [
            'type' => $data['type'] ?? 'system',
            'category' => $data['category'] ?? 'system',
            'priority' => $data['priority'] ?? 'normal',
        ];

        $message = $message->withData(array_filter($payload));

        return $message;
    }

    /**
     * Enregistrer ou mettre à jour un token
     */
    public function registerToken(
        User $user,
        string $token,
        string $platform,
        array $deviceInfo = []
    ): DeviceToken {
        // Désactiver les anciens tokens du même device
        if (!empty($deviceInfo['device_id'])) {
            DeviceToken::where('user_id', $user->id)
                ->where('device_id', $deviceInfo['device_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        // Créer ou mettre à jour le token
        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_id' => $deviceInfo['device_id'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'device_model' => $deviceInfo['device_model'] ?? null,
                'os_version' => $deviceInfo['os_version'] ?? null,
                'app_version' => $deviceInfo['app_version'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Supprimer un token
     */
    public function unregisterToken(string $token): bool
    {
        return DeviceToken::where('token', $token)->delete() > 0;
    }

    /**
     * Supprimer tous les tokens d'un user
     */
    public function unregisterAllTokens(User $user): int
    {
        return $user->deviceTokens()->delete();
    }
}
