<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\Auth\SocialAuthRequest;
use App\Services\SocialAuthService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class SocialAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService,
        protected TokenService $tokenService
    ) {}

    /**
     * Authenticate (login or register) via social provider.
     *
     * @param SocialAuthRequest $request
     * @param string $provider 'google' or 'apple'
     * @return JsonResponse
     */
    public function authenticate(SocialAuthRequest $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return $this->error('Invalid provider', 400);
        }

        try {
            // Verify the token and get user data
            $socialUser = match ($provider) {
                'google' => $this->socialAuthService->verifyGoogleToken($request->id_token),
                'apple' => $this->socialAuthService->verifyAppleToken(
                    $request->id_token,
                    $request->appleUserData()
                ),
            };

            // Find or create the user
            $user = $this->socialAuthService->findOrCreateUser($provider, $socialUser);

            // Generate tokens
            $tokenData = $this->tokenService->createTokenPair(
                $user,
                $request->device_name,
                $request->device_id
            );

            // Include auth methods info
            $tokenData['authMethods'] = $user->getAuthMethods();
            $tokenData['needsPinSetup'] = $user->needsPinSetup();
            $tokenData['socialAuth'] = [
                'provider' => $provider,
                'linkedGoogle' => $user->linkedToGoogle(),
                'linkedApple' => $user->linkedToApple(),
            ];

            return $this->success($tokenData, 'Authentication successful');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            report($e);
            return $this->error('Authentication failed', 500);
        }
    }

    /**
     * Link a social account to the authenticated user.
     *
     * @param SocialAuthRequest $request
     * @param string $provider 'google' or 'apple'
     * @return JsonResponse
     */
    public function link(SocialAuthRequest $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return $this->error('Invalid provider', 400);
        }

        $user = $request->user();

        // Check if already linked
        $field = $provider . '_id';
        if (!empty($user->$field)) {
            return $this->error("Your account is already linked to {$provider}", 400);
        }

        try {
            // Verify the token and get user data
            $socialUser = match ($provider) {
                'google' => $this->socialAuthService->verifyGoogleToken($request->id_token),
                'apple' => $this->socialAuthService->verifyAppleToken(
                    $request->id_token,
                    $request->appleUserData()
                ),
            };

            // Link the account
            $user = $this->socialAuthService->linkAccount($user, $provider, $socialUser);

            return $this->success([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'linkedGoogle' => $user->linkedToGoogle(),
                    'linkedApple' => $user->linkedToApple(),
                ],
            ], 'Account linked successfully');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (\Exception $e) {
            report($e);
            return $this->error('Failed to link account', 500);
        }
    }

    /**
     * Unlink a social account from the authenticated user.
     *
     * @param string $provider 'google' or 'apple'
     * @return JsonResponse
     */
    public function unlink(string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return $this->error('Invalid provider', 400);
        }

        $user = auth()->user();
        $field = $provider . '_id';

        if (empty($user->$field)) {
            return $this->error("Your account is not linked to {$provider}", 400);
        }

        // Ensure user has another way to authenticate
        $hasPassword = $user->canAuthenticateWithPassword();
        $hasPin = $user->canAuthenticateWithPin();
        $hasPhone = !empty($user->phone);
        $otherSocial = ($provider === 'google' && $user->linkedToApple())
            || ($provider === 'apple' && $user->linkedToGoogle());

        if (!$hasPassword && !$hasPin && !$hasPhone && !$otherSocial) {
            return $this->error(
                'Cannot unlink. You need at least one way to authenticate (phone, password, PIN, or another social account).',
                400
            );
        }

        $user->update([$field => null]);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'linkedGoogle' => $user->linkedToGoogle(),
                'linkedApple' => $user->linkedToApple(),
            ],
        ], 'Account unlinked successfully');
    }
}
