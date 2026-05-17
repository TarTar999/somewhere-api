<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    public function show(): JsonResponse
    {
        $user = auth()->user();
        $user->load(['settings', 'collections']);

        return $this->success($this->formatUser($user));
    }

    public function update(UpdateProfileRequest $request, User $user): JsonResponse
    {
        // Ensure user can only update their own profile
        if (auth()->id() !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        $userData = [];

        if ($request->has('firstName')) {
            $userData['first_name'] = $request->firstName;
        }
        if ($request->has('lastName')) {
            $userData['last_name'] = $request->lastName;
        }
        if ($request->has('email')) {
            $userData['email'] = $request->email;
        }
        if ($request->has('phone')) {
            $userData['phone'] = $request->phone;
        }
        if ($request->has('sex')) {
            $userData['sex'] = $request->sex;
        }
        if ($request->has('nuiNumber')) {
            $userData['nui_number'] = $request->nuiNumber;
        }
        if ($request->has('cniNumber')) {
            $userData['cni_number'] = $request->cniNumber;
        }
        if ($request->has('cniExpirationDate')) {
            $userData['cni_expiration_date'] = $request->cniExpirationDate;
        }

        if (!empty($userData)) {
            // Update name field for backward compatibility
            if (isset($userData['first_name']) || isset($userData['last_name'])) {
                $firstName = $userData['first_name'] ?? $user->first_name;
                $lastName = $userData['last_name'] ?? $user->last_name;
                $userData['name'] = trim("$firstName $lastName");
            }
            $user->update($userData);
        }

        // Update settings if provided
        if ($request->has('settings')) {
            $settingsData = [];
            $settings = $request->settings;

            if (isset($settings['language'])) {
                $settingsData['language'] = $settings['language'];
            }
            if (isset($settings['unit'])) {
                $settingsData['unit'] = $settings['unit'];
            }
            if (isset($settings['notifications'])) {
                $settingsData['notifications'] = $settings['notifications'];
            }
            if (isset($settings['mapType'])) {
                $settingsData['map_type'] = $settings['mapType'];
            }
            if (isset($settings['googleSearch'])) {
                $settingsData['google_search'] = $settings['googleSearch'];
            }
            if (isset($settings['isCityMapper'])) {
                $settingsData['is_city_mapper'] = $settings['isCityMapper'];
            }
            if (isset($settings['darkMode'])) {
                $settingsData['dark_mode'] = $settings['darkMode'];
            }

            if (!empty($settingsData)) {
                $userSettings = $user->getOrCreateSettings();
                $userSettings->update($settingsData);
            }
        }

        $user->refresh();
        $user->load('settings');

        return $this->success($this->formatUser($user), 'Profile updated successfully');
    }

    public function destroy(User $user): JsonResponse
    {
        // Ensure user can only delete their own account
        if (auth()->id() !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        // Revoke all tokens
        $this->tokenService->revokeAllTokens($user);

        // Soft delete the user
        $user->delete();

        return $this->noContent();
    }

    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'sex' => $user->sex,
            'nuiNumber' => $user->nui_number,
            'cniNumber' => $user->cni_number,
            'cniExpirationDate' => $user->cni_expiration_date?->toISOString(),
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
            'collections' => $user->collections?->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'type' => $c->type,
            ]),
        ];
    }
}
