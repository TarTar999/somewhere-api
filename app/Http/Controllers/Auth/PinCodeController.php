<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PinCodeController extends Controller
{
    /**
     * Create a new PIN code for the authenticated user.
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

        $user = $request->user();

        $user->update([
            'pin_code' => $request->pin_code,
            'has_pin_code' => true,
        ]);

        return response()->json([
            'message' => 'Code PIN créé avec succès.',
        ]);
    }

    /**
     * Update the PIN code for the authenticated user.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'pin_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'pin_code_confirmation' => ['required', 'same:pin_code'],
        ], [
            'current_password.required' => 'Le mot de passe actuel est requis.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'pin_code.required' => 'Le code PIN est requis.',
            'pin_code.size' => 'Le code PIN doit contenir exactement 6 chiffres.',
            'pin_code.regex' => 'Le code PIN doit contenir uniquement des chiffres.',
            'pin_code_confirmation.same' => 'La confirmation du code PIN ne correspond pas.',
        ]);

        $request->user()->update([
            'pin_code' => $request->pin_code,
            'has_pin_code' => true,
        ]);

        return response()->json([
            'message' => 'Code PIN mis à jour avec succès.',
        ]);
    }

    /**
     * Skip PIN code setup (mark as dismissed for this session).
     */
    public function skip(Request $request): JsonResponse
    {
        // Store in session that user has skipped PIN setup
        session(['pin_setup_skipped' => true]);

        return response()->json([
            'message' => 'Configuration du code PIN ignorée.',
        ]);
    }
}
