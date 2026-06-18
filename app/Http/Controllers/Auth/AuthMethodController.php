<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthMethodController extends Controller
{
    /**
     * Check authentication methods available for a phone number.
     */
    public function checkAuthMethods(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = SmsService::normalizePhone($request->phone);

        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->orWhere('phone', '+' . $phone)
            ->first();

        if (!$user) {
            // Don't reveal if user exists - return generic response
            return response()->json([
                'exists' => false,
                'methods' => [
                    'password' => false,
                    'pin_code' => false,
                ],
            ]);
        }

        return response()->json([
            'exists' => true,
            'methods' => $user->getAuthMethods(),
        ]);
    }
}
