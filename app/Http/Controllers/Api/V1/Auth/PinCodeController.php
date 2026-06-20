<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PinCodeController extends Controller
{
    /**
     * Create a new PIN code for the authenticated user.
     * Used after first OTP login for passwordless users.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'pin_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'pin_code_confirmation' => ['required', 'same:pin_code'],
        ], [
            'pin_code.required' => 'Le code PIN est requis.',
            'pin_code.size' => 'Le code PIN doit contenir exactement 6 chiffres.',
            'pin_code.regex' => 'Le code PIN doit contenir uniquement des chiffres.',
            'pin_code_confirmation.same' => 'La confirmation du code PIN ne correspond pas.',
        ]);

        $user = auth()->user();

        // Check if user already has PIN
        if ($user->canAuthenticateWithPin()) {
            return $this->error('Un code PIN existe déjà. Utilisez PUT pour le modifier.', 409);
        }

        $user->update([
            'pin_code' => $request->pin_code,
            'has_pin_code' => true,
        ]);

        return $this->success([
            'message' => 'Code PIN créé avec succès.',
            'authMethods' => $user->getAuthMethods(),
            'needsPinSetup' => false,
        ], 'Code PIN créé avec succès', 201);
    }

    /**
     * Update the PIN code for the authenticated user.
     * Requires current PIN or password for verification.
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Determine validation rules based on user's auth methods
        $rules = [
            'pin_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'pin_code_confirmation' => ['required', 'same:pin_code'],
        ];

        // Require verification: either current PIN or current password
        if ($user->canAuthenticateWithPin()) {
            $rules['current_pin_code'] = ['required_without:current_password', 'string', 'size:6'];
        }
        if ($user->canAuthenticateWithPassword()) {
            $rules['current_password'] = ['required_without:current_pin_code', 'string'];
        }

        $request->validate($rules, [
            'pin_code.required' => 'Le nouveau code PIN est requis.',
            'pin_code.size' => 'Le code PIN doit contenir exactement 6 chiffres.',
            'pin_code.regex' => 'Le code PIN doit contenir uniquement des chiffres.',
            'pin_code_confirmation.same' => 'La confirmation du code PIN ne correspond pas.',
            'current_pin_code.required_without' => 'Le code PIN actuel ou le mot de passe est requis.',
            'current_password.required_without' => 'Le mot de passe actuel ou le code PIN est requis.',
        ]);

        // Verify current credentials
        $verified = false;

        if ($request->filled('current_pin_code') && $user->canAuthenticateWithPin()) {
            $pinHash = $user->getAttributes()['pin_code'] ?? null;
            if ($pinHash && Hash::check($request->current_pin_code, $pinHash)) {
                $verified = true;
            }
        }

        if (!$verified && $request->filled('current_password') && $user->canAuthenticateWithPassword()) {
            if (Hash::check($request->current_password, $user->password)) {
                $verified = true;
            }
        }

        if (!$verified) {
            return $this->error('Les identifiants actuels sont incorrects.', 401);
        }

        $user->update([
            'pin_code' => $request->pin_code,
            'has_pin_code' => true,
        ]);

        return $this->success([
            'message' => 'Code PIN mis à jour avec succès.',
            'authMethods' => $user->getAuthMethods(),
        ], 'Code PIN mis à jour avec succès');
    }

    /**
     * Delete PIN code (requires password if available, or OTP re-verification).
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->canAuthenticateWithPin()) {
            return $this->error('Aucun code PIN configuré.', 404);
        }

        // For security, require password to delete PIN (if user has password)
        if ($user->canAuthenticateWithPassword()) {
            $request->validate([
                'current_password' => ['required', 'string'],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->error('Mot de passe incorrect.', 401);
            }
        }

        $user->update([
            'pin_code' => null,
            'has_pin_code' => false,
        ]);

        return $this->success([
            'message' => 'Code PIN supprimé avec succès.',
            'authMethods' => $user->getAuthMethods(),
            'needsPinSetup' => $user->needsPinSetup(),
        ], 'Code PIN supprimé avec succès');
    }
}
