<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    /**
     * Enregistrer un token de notification push
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
            'device_id' => 'nullable|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:50',
        ]);

        $deviceToken = $this->pushService->registerToken(
            auth()->user(),
            $validated['token'],
            $validated['platform'],
            [
                'device_id' => $validated['device_id'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'device_model' => $validated['device_model'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
            ]
        );

        return $this->success([
            'id' => $deviceToken->id,
            'platform' => $deviceToken->platform,
            'device_name' => $deviceToken->device_name,
            'registered_at' => $deviceToken->created_at->toIso8601String(),
        ], 'Token enregistré avec succès');
    }

    /**
     * Supprimer un token de notification push
     */
    public function unregister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $deleted = $this->pushService->unregisterToken($validated['token']);

        if ($deleted) {
            return $this->success(null, 'Token supprimé avec succès');
        }

        return $this->error('Token non trouvé', 404);
    }

    /**
     * Lister les tokens de l'utilisateur
     */
    public function index(): JsonResponse
    {
        $tokens = auth()->user()->deviceTokens()
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'platform' => $token->platform,
                'device_name' => $token->device_name,
                'device_model' => $token->device_model,
                'os_version' => $token->os_version,
                'app_version' => $token->app_version,
                'is_active' => $token->is_active,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at->toIso8601String(),
            ]);

        return $this->success($tokens);
    }

    /**
     * Supprimer un token par ID
     */
    public function destroy(int $id): JsonResponse
    {
        $token = auth()->user()->deviceTokens()->find($id);

        if (!$token) {
            return $this->error('Token non trouvé', 404);
        }

        $token->delete();

        return $this->noContent();
    }

    /**
     * Supprimer tous les tokens de l'utilisateur
     */
    public function destroyAll(): JsonResponse
    {
        $count = $this->pushService->unregisterAllTokens(auth()->user());

        return $this->success([
            'deleted_count' => $count,
        ], 'Tous les tokens ont été supprimés');
    }
}
