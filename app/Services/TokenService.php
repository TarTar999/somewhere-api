<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;

class TokenService
{
    public function createTokenPair(User $user, ?string $deviceName = null, ?string $deviceId = null): array
    {
        // Create access token with Sanctum (expires in 60 min)
        $accessToken = $user->createToken(
            $deviceName ?? 'mobile-app',
            ['*'],
            now()->addMinutes(60)
        );

        // Create refresh token (expires in 30 days)
        $refreshTokenData = RefreshToken::generate($user, $deviceName, $deviceId, 30);

        return [
            'user' => $this->formatUser($user),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshTokenData['plain_token'],
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 60 minutes in seconds
        ];
    }

    public function refreshAccessToken(string $refreshToken): ?array
    {
        $token = RefreshToken::findByPlainToken($refreshToken);

        if (!$token) {
            return null;
        }

        $user = $token->user;

        // Revoke old refresh token
        $token->revoke();

        // Create new token pair
        return $this->createTokenPair($user, $token->device_name, $token->device_id);
    }

    public function revokeAllTokens(User $user): void
    {
        // Revoke all Sanctum tokens
        $user->tokens()->delete();

        // Revoke all refresh tokens
        $user->refreshTokens()->update(['revoked_at' => now()]);
    }

    public function revokeCurrentToken(User $user): void
    {
        // Revoke current Sanctum token
        $user->currentAccessToken()?->delete();
    }

    protected function formatUser(User $user): array
    {
        $user->load([
            'settings',
            'addresses' => function ($query) {
                $query->with('street')->latest()->limit(1);
            },
            'tracks' => function ($query) {
                $query->latest();
            },
            'sharedTracks' => function ($query) {
                $query->with('user')->latest();
            },
        ]);

        $lastAddress = $user->addresses->first();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'fullName' => $user->full_name,
            'initials' => $user->initials,
            'sex' => $user->sex,
            'lottieAvatar' => $user->lottie_avatar,
            'nuiNumber' => $user->nui_number,
            'cniNumber' => $user->cni_number,
            'cniExpirationDate' => $user->cni_expiration_date?->toISOString(),
            'hasSignature' => !empty($user->signature),
            'settings' => $user->settings ? [
                'language' => $user->settings->language,
                'unit' => $user->settings->unit,
                'notifications' => $user->settings->notifications,
                'mapType' => $user->settings->map_type,
                'proofOfResidence' => $user->settings->proof_of_residence,
                'proofOfResidenceDate' => $user->settings->proof_of_residence_date?->toISOString(),
                'googleSearch' => $user->settings->google_search,
                'isCityMapper' => $user->settings->is_city_mapper,
                'darkMode' => $user->settings->dark_mode,
            ] : null,
            'address' => $lastAddress ? $this->formatAddress($lastAddress) : null,
            'tracks' => [
                'owned' => $user->tracks->map(fn($t) => $this->formatTrack($t))->toArray(),
                'shared' => $user->sharedTracks->map(fn($t) => $this->formatTrack($t, true))->toArray(),
            ],
        ];
    }

    protected function formatAddress($address): array
    {
        return [
            'id' => $address->id,
            'swAddress' => $address->sw_address,
            'displayName' => $address->display_name,
            'latLon' => $address->lat_lon,
            'coordinates' => $address->coordinates,
            'localization' => $address->localization,
            'way' => $address->way,
            'houseType' => $address->house_type,
            'homeStatus' => $address->home_status,
            'description' => $address->description,
            'verificationStatus' => $address->verification_status,
            'createdAt' => $address->created_at->toISOString(),
            'updatedAt' => $address->updated_at->toISOString(),
        ];
    }

    protected function formatTrack($track, bool $isShared = false): array
    {
        $data = [
            'id' => $track->id,
            'name' => $track->name,
            'description' => $track->description,
            'structure' => $track->structure,
            'color' => $track->color,
            'distance' => $track->distance,
            'pointsCount' => $track->points_count,
            'isPublic' => $track->is_public,
            'shareToken' => $track->share_token,
            'shareUrl' => url("/api/tracks/shared/{$track->share_token}"),
            'createdAt' => $track->created_at->toISOString(),
            'updatedAt' => $track->updated_at->toISOString(),
        ];

        if ($isShared) {
            $data['permission'] = $track->pivot->permission ?? 'view';
            $data['owner'] = [
                'id' => $track->user->id,
                'name' => $track->user->full_name,
            ];
        }

        return $data;
    }
}
