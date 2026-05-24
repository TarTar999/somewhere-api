<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class PushNotificationService
{
    protected ?string $serverKey;
    protected ?string $projectId;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
        $this->projectId = config('services.firebase.project_id');
    }

    /**
     * Vérifie si Firebase est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->serverKey);
    }

    /**
     * Envoyer une push notification à un utilisateur
     */
    public function sendToUser(User $user, Notification $notification): array
    {
        $tokens = $user->deviceTokens()->active()->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info('No active device tokens for user', ['user_id' => $user->id]);
            return ['success' => false, 'reason' => 'no_tokens'];
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
            return ['success' => false, 'reason' => 'not_configured'];
        }

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'invalid_tokens' => [],
        ];

        // FCM permet d'envoyer à 1000 tokens max par requête
        $chunks = array_chunk($tokens, 1000);

        foreach ($chunks as $chunk) {
            $response = $this->sendFcmRequest($chunk, $notification);

            if ($response['success']) {
                $results['sent'] += $response['success_count'];
                $results['failed'] += $response['failure_count'];

                // Collecter les tokens invalides pour les désactiver
                if (!empty($response['invalid_tokens'])) {
                    $results['invalid_tokens'] = array_merge(
                        $results['invalid_tokens'],
                        $response['invalid_tokens']
                    );
                }
            }
        }

        // Désactiver les tokens invalides
        if (!empty($results['invalid_tokens'])) {
            $this->deactivateInvalidTokens($results['invalid_tokens']);
        }

        $notification->markAsSent();

        return $results;
    }

    /**
     * Envoyer la requête à FCM
     */
    protected function sendFcmRequest(array $tokens, Notification $notification): array
    {
        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'category' => $notification->category,
                'priority' => $notification->priority,
                'action_type' => $notification->action_type,
                'action_url' => $notification->action_url,
                'data' => json_encode($notification->data),
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'priority' => $notification->priority === 'urgent' ? 'high' : 'normal',
            'content_available' => true,
        ];

        // Ajouter les options spécifiques Android
        $payload['android'] = [
            'priority' => $notification->priority === 'urgent' ? 'high' : 'normal',
            'notification' => [
                'channel_id' => $this->getChannelId($notification->category),
                'icon' => 'ic_notification',
                'color' => '#4F46E5',
            ],
        ];

        // Ajouter les options spécifiques iOS
        $payload['apns'] = [
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $notification->title,
                        'body' => $notification->body,
                    ],
                    'sound' => 'default',
                    'badge' => 1,
                    'mutable-content' => 1,
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $result = [
                    'success' => true,
                    'success_count' => $data['success'] ?? 0,
                    'failure_count' => $data['failure'] ?? 0,
                    'invalid_tokens' => [],
                ];

                // Identifier les tokens invalides
                if (isset($data['results'])) {
                    foreach ($data['results'] as $index => $res) {
                        if (isset($res['error'])) {
                            $error = $res['error'];
                            if (in_array($error, ['InvalidRegistration', 'NotRegistered', 'MismatchSenderId'])) {
                                $result['invalid_tokens'][] = $tokens[$index];
                            }
                        }
                    }
                }

                Log::info('FCM push sent', [
                    'success' => $result['success_count'],
                    'failure' => $result['failure_count'],
                ]);

                return $result;
            }

            Log::error('FCM request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => count($tokens),
                'invalid_tokens' => [],
            ];

        } catch (\Exception $e) {
            Log::error('FCM request exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => count($tokens),
                'invalid_tokens' => [],
            ];
        }
    }

    /**
     * Désactiver les tokens invalides
     */
    protected function deactivateInvalidTokens(array $tokens): void
    {
        DeviceToken::whereIn('token', $tokens)->update(['is_active' => false]);

        Log::info('Deactivated invalid tokens', ['count' => count($tokens)]);
    }

    /**
     * Obtenir le channel ID Android selon la catégorie
     */
    protected function getChannelId(string $category): string
    {
        return match ($category) {
            Notification::CATEGORY_DOCUMENT => 'documents',
            Notification::CATEGORY_KYC => 'kyc',
            Notification::CATEGORY_PAYMENT => 'payments',
            Notification::CATEGORY_ENGAGEMENT => 'engagement',
            default => 'general',
        };
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
        // Chercher si ce token existe déjà
        $existingToken = DeviceToken::where('token', $token)->first();

        if ($existingToken) {
            // Si le token appartient à un autre user, le transférer
            if ($existingToken->user_id !== $user->id) {
                $existingToken->update(['user_id' => $user->id]);
            }

            // Mettre à jour les infos
            $existingToken->update([
                'platform' => $platform,
                'device_id' => $deviceInfo['device_id'] ?? $existingToken->device_id,
                'device_name' => $deviceInfo['device_name'] ?? $existingToken->device_name,
                'device_model' => $deviceInfo['device_model'] ?? $existingToken->device_model,
                'os_version' => $deviceInfo['os_version'] ?? $existingToken->os_version,
                'app_version' => $deviceInfo['app_version'] ?? $existingToken->app_version,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            return $existingToken;
        }

        // Créer un nouveau token
        return DeviceToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'platform' => $platform,
            'device_id' => $deviceInfo['device_id'] ?? null,
            'device_name' => $deviceInfo['device_name'] ?? null,
            'device_model' => $deviceInfo['device_model'] ?? null,
            'os_version' => $deviceInfo['os_version'] ?? null,
            'app_version' => $deviceInfo['app_version'] ?? null,
            'is_active' => true,
            'last_used_at' => now(),
        ]);
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
