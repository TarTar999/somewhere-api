<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\RefreshTokenRequest;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\SmsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        protected TokenService $tokenService,
        protected SmsService $smsService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        // Normalize phone number
        $phone = SmsService::normalizePhone($request->phone);

        // Find user by phone (try multiple formats)
        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->orWhere('phone', '+' . $phone)
            ->first();

        if (!$user) {
            return $this->error('Identifiants invalides', 401);
        }

        $authenticated = false;

        // Check PIN code authentication
        if ($request->filled('pin_code')) {
            if (!$user->canAuthenticateWithPin()) {
                return $this->error('Code PIN non configuré. Connectez-vous via OTP pour créer votre code PIN.', 401, [
                    'error_code' => 'PIN_NOT_CONFIGURED',
                    'authMethods' => $user->getAuthMethods(),
                ]);
            }

            $pinHash = $user->getAttributes()['pin_code'] ?? null;
            if ($pinHash && Hash::check($request->pin_code, $pinHash)) {
                $authenticated = true;
            } else {
                return $this->error('Code PIN incorrect', 401);
            }
        }
        // Check password authentication
        elseif ($request->filled('password')) {
            if (!$user->canAuthenticateWithPassword()) {
                return $this->error('Aucun mot de passe configuré. Utilisez votre code PIN ou connectez-vous via OTP.', 401, [
                    'error_code' => 'PASSWORD_NOT_SET',
                    'authMethods' => $user->getAuthMethods(),
                ]);
            }

            if (Hash::check($request->password, $user->password)) {
                $authenticated = true;
            } else {
                return $this->error('Mot de passe incorrect', 401);
            }
        }

        if (!$authenticated) {
            return $this->error('Identifiants invalides', 401);
        }

        $tokenData = $this->tokenService->createTokenPair(
            $user,
            $request->device_name,
            $request->device_id
        );

        // Include auth methods info
        $tokenData['authMethods'] = $user->getAuthMethods();
        $tokenData['needsPinSetup'] = $user->needsPinSetup();

        return $this->success($tokenData, 'Login successful');
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // Phone is already normalized by RegisterRequest::prepareForValidation()
        $userData = [
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'name' => $request->firstName . ' ' . $request->lastName,
            'email' => $request->email,
            'phone' => $request->phone,
            'sex' => $request->civility,
            'cni_number' => $request->cni,
            'nui_number' => $request->nui,
            'cni_expiration_date' => $request->cniExpiration,
        ];

        // Only set password if provided (passwordless registration for mobile)
        if ($request->filled('password')) {
            $userData['password'] = $request->password;
        }

        $user = User::create($userData);

        // Settings and default collections are created by UserObserver

        $tokenData = $this->tokenService->createTokenPair($user);

        // Include auth methods info for client to know what's available
        $tokenData['authMethods'] = $user->getAuthMethods();
        $tokenData['needsPinSetup'] = $user->needsPinSetup();

        return $this->success($tokenData, 'Registration successful', 201);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokenData = $this->tokenService->refreshAccessToken($request->token);

        if (!$tokenData) {
            return $this->error('Invalid or expired refresh token', 401);
        }

        return $this->success($tokenData, 'Token refreshed');
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();

        if ($user) {
            $this->tokenService->revokeCurrentToken($user);
        }

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Send OTP for phone-based login
     */
    public function sendLoginOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Normalize phone number
        $phone = SmsService::normalizePhone($request->phone);

        // Check if user exists with this phone (try multiple formats)
        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->orWhere('phone', '+' . $phone)
            ->first();

        error_log("\n🔐 ===== LOGIN OTP =====");
        error_log("Phone (original): {$request->phone}");
        error_log("Phone (normalized): {$phone}");
        error_log("User found: " . ($user ? "Yes (ID: {$user->id})" : "No"));

        if (!$user) {
            // In production, don't reveal if user exists
            if (config('app.env') !== 'local') {
                return $this->success([
                    'expiresAt' => now()->addMinutes(10)->toIso8601String(),
                ], 'If this phone number is registered, you will receive an OTP');
            }
            // In development, still send OTP for testing
            error_log("Dev mode: Sending OTP anyway for testing");
        }

        // Generate OTP with normalized phone
        $otp = OtpCode::generate(
            $phone,
            'phone',
            'login',
            10 // 10 minutes expiry
        );

        // Send SMS
        $sent = $this->smsService->sendOtp($phone, $otp->code);

        if (!$sent && config('app.env') !== 'local') {
            return $this->error('Failed to send OTP. Please try again.', 500);
        }

        $response = [
            'expiresAt' => $otp->expires_at->toIso8601String(),
        ];

        // In development, include the code for testing
        if (config('app.env') === 'local') {
            $response['code'] = $otp->code;
        }

        return $this->success($response, 'OTP sent successfully');
    }

    /**
     * Login using OTP (phone-based authentication)
     */
    public function loginWithOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string|min:6|max:7',
            'device_name' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Normalize phone and code
        $phone = SmsService::normalizePhone($request->phone);
        $normalizedCode = str_replace('-', '', $request->code);

        error_log("\n🔓 ===== LOGIN WITH OTP =====");
        error_log("Phone (normalized): {$phone}");
        error_log("Code: {$request->code} -> {$normalizedCode}");

        // Find valid OTP
        $otp = OtpCode::where('identifier', $phone)
            ->where('type', 'phone')
            ->where('purpose', 'login')
            ->valid()
            ->get()
            ->first(fn($o) => str_replace('-', '', $o->code) === $normalizedCode);

        error_log("OTP found: " . ($otp ? "Yes" : "No"));

        if (!$otp) {
            return $this->error('Invalid or expired code', 401);
        }

        // Find user (try multiple formats)
        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->orWhere('phone', '+' . $phone)
            ->first();

        if (!$user) {
            return $this->error('User not found', 404);
        }

        // Mark OTP as verified and delete it
        $otp->markAsVerified();
        $otp->delete();

        // Generate tokens
        $tokenData = $this->tokenService->createTokenPair(
            $user,
            $request->device_name,
            $request->device_id
        );

        // Include auth methods info for client to know PIN setup is needed
        $tokenData['authMethods'] = $user->getAuthMethods();
        $tokenData['needsPinSetup'] = $user->needsPinSetup();
        $tokenData['isPasswordless'] = $user->isPasswordless();

        return $this->success($tokenData, 'Login successful');
    }
}
