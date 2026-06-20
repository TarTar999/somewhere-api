<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;

class TokenService
{
    public function createTokenPair(User $user, ?string $deviceName = null, ?string $deviceId = null, bool $isMobile = true): array
    {
        // Mobile: 7 days access token, 90 days refresh token
        // Web: 60 minutes access token, 30 days refresh token
        $accessTokenMinutes = $isMobile
            ? (int) config('auth.mobile_access_token_ttl', 7 * 24 * 60)  // 7 days
            : (int) config('auth.web_access_token_ttl', 60);             // 60 minutes

        $refreshTokenDays = $isMobile
            ? (int) config('auth.mobile_refresh_token_ttl', 90)  // 90 days
            : (int) config('auth.web_refresh_token_ttl', 30);    // 30 days

        // Create access token with Sanctum
        $accessToken = $user->createToken(
            $deviceName ?? 'mobile-app',
            ['*'],
            now()->addMinutes($accessTokenMinutes)
        );

        // Create refresh token
        $refreshTokenData = RefreshToken::generate($user, $deviceName, $deviceId, $refreshTokenDays);

        return [
            'user' => $this->formatUser($user),
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshTokenData['plain_token'],
            'token_type' => 'Bearer',
            'expires_in' => $accessTokenMinutes * 60, // in seconds
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
            'cniExpirationDate' => $user->cni_expiration_date?->toIso8601String(),
            'hasSignature' => !empty($user->signature),
            'settings' => $user->settings ? [
                'language' => $user->settings->language,
                'unit' => $user->settings->unit,
                'notifications' => $user->settings->notifications,
                'mapType' => $user->settings->map_type,
                'proofOfResidence' => $user->settings->proof_of_residence,
                'proofOfResidenceDate' => $user->settings->proof_of_residence_date?->toIso8601String(),
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
            'createdAt' => $address->created_at->toIso8601String(),
            'updatedAt' => $address->updated_at->toIso8601String(),
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
            'createdAt' => $track->created_at->toIso8601String(),
            'updatedAt' => $track->updated_at->toIso8601String(),
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
