<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Google_Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SocialAuthService
{
    /**
     * Verify a Google ID token and return the user payload.
     *
     * @param string $idToken
     * @return array{id: string, email: ?string, firstName: ?string, lastName: ?string}
     * @throws InvalidArgumentException
     */
    public function verifyGoogleToken(string $idToken): array
    {
        // Accept tokens from web, iOS, and Android clients
        $clientIds = array_filter([
            config('services.google.client_id'),
            config('services.google.client_id_ios'),
            config('services.google.client_id_android'),
        ]);

        if (empty($clientIds)) {
            throw new InvalidArgumentException('Google client IDs not configured');
        }

        // Create client and set the first client ID (required for initialization)
        $client = new Google_Client(['client_id' => $clientIds[0]]);

        // Verify the token - pass all valid client IDs as accepted audiences
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            throw new InvalidArgumentException('Invalid Google token');
        }

        // Verify the audience is one of our valid client IDs
        if (!in_array($payload['aud'], $clientIds)) {
            Log::warning('Google token audience mismatch', [
                'received_aud' => $payload['aud'],
                'valid_auds' => $clientIds,
            ]);
            throw new InvalidArgumentException('Invalid Google token audience');
        }

        // Extract name parts
        $firstName = $payload['given_name'] ?? null;
        $lastName = $payload['family_name'] ?? null;

        // Fallback: if no given_name, try to split name
        if (!$firstName && isset($payload['name'])) {
            $nameParts = explode(' ', $payload['name'], 2);
            $firstName = $nameParts[0] ?? null;
            $lastName = $nameParts[1] ?? $lastName;
        }

        return [
            'id' => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];
    }

    /**
     * Verify an Apple ID token and return the user payload.
     *
     * @param string $idToken
     * @param array|null $userData Optional user data (Apple only sends on first auth)
     * @return array{id: string, email: ?string, firstName: ?string, lastName: ?string}
     * @throws InvalidArgumentException
     */
    public function verifyAppleToken(string $idToken, ?array $userData = null): array
    {
        // Fetch Apple's public keys
        $publicKeys = $this->getApplePublicKeys();

        try {
            // Decode and verify the JWT
            $decoded = JWT::decode($idToken, $publicKeys);

            // Verify the issuer
            if ($decoded->iss !== 'https://appleid.apple.com') {
                throw new InvalidArgumentException('Invalid Apple token issuer');
            }

            // Verify the audience (your client ID)
            $validAudiences = array_filter([
                config('services.apple.client_id'),
                config('services.apple.bundle_id'),
            ]);

            if (!in_array($decoded->aud, $validAudiences)) {
                Log::warning('Apple token audience mismatch', [
                    'received_aud' => $decoded->aud,
                    'valid_auds' => $validAudiences,
                ]);
                throw new InvalidArgumentException('Invalid Apple token audience');
            }

            // Verify expiration
            if ($decoded->exp < time()) {
                throw new InvalidArgumentException('Apple token has expired');
            }

            // Apple only sends email and name on first authorization
            // After that, we need to rely on the sub (user ID)
            $email = $decoded->email ?? null;

            // Use provided user data if available (first-time auth)
            $firstName = $userData['firstName'] ?? null;
            $lastName = $userData['lastName'] ?? null;

            return [
                'id' => $decoded->sub,
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
            ];
        } catch (\Exception $e) {
            Log::error('Apple token verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Invalid Apple token: ' . $e->getMessage());
        }
    }

    /**
     * Find an existing user or create a new one based on social auth data.
     *
     * @param string $provider 'google' or 'apple'
     * @param array $socialUser
     * @return User
     */
    public function findOrCreateUser(string $provider, array $socialUser): User
    {
        $field = $provider . '_id';

        // 1. Look for user by provider ID
        $user = User::where($field, $socialUser['id'])->first();
        if ($user) {
            return $user;
        }

        // 2. Look for user by email (if provided) and link the account
        if (!empty($socialUser['email'])) {
            $user = User::where('email', $socialUser['email'])->first();
            if ($user) {
                $user->update([$field => $socialUser['id']]);
                return $user;
            }
        }

        // 3. Create a new user
        return User::create([
            $field => $socialUser['id'],
            'email' => $socialUser['email'],
            'first_name' => $socialUser['firstName'] ?? '',
            'last_name' => $socialUser['lastName'] ?? '',
            // phone is null - user can add it later
            // password is null - user authenticates via social
        ]);
    }

    /**
     * Link a social account to an existing user.
     *
     * @param User $user
     * @param string $provider
     * @param array $socialUser
     * @return User
     * @throws InvalidArgumentException
     */
    public function linkAccount(User $user, string $provider, array $socialUser): User
    {
        $field = $provider . '_id';

        // Check if this social account is already linked to another user
        $existing = User::where($field, $socialUser['id'])
            ->where('id', '!=', $user->id)
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('This social account is already linked to another user');
        }

        // Link the account and update email if not set
        $updates = [$field => $socialUser['id']];

        if (empty($user->email) && !empty($socialUser['email'])) {
            // Check if email is already used by another user
            $emailExists = User::where('email', $socialUser['email'])
                ->where('id', '!=', $user->id)
                ->exists();

            if (!$emailExists) {
                $updates['email'] = $socialUser['email'];
            }
        }

        $user->update($updates);

        return $user->fresh();
    }

    /**
     * Get Apple's public keys for JWT verification.
     *
     * @return array
     */
    protected function getApplePublicKeys(): array
    {
        // Cache the raw JSON keys for 24 hours (OpenSSL keys cannot be serialized)
        $keysJson = Cache::remember('apple_public_keys_json', 86400, function () {
            $response = Http::get('https://appleid.apple.com/auth/keys');

            if (!$response->successful()) {
                throw new InvalidArgumentException('Failed to fetch Apple public keys');
            }

            return $response->json();
        });

        // Parse the keys each time (cannot cache OpenSSL objects)
        return JWK::parseKeySet($keysJson);
    }
}
