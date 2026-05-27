<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Send OTP for password reset (via phone)
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Normalize phone number
        $phone = SmsService::normalizePhone($request->phone);

        // Check if user exists with this phone (try both formats)
        /** @var User|null $user */
        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->orWhere('phone', '+' . $phone)
            ->first();

        error_log("\n🔐 ===== FORGOT PASSWORD =====");
        error_log("Phone (original): {$request->phone}");
        error_log("Phone (normalized): {$phone}");
        error_log("User found: " . ($user ? "Yes (ID: {$user->id})" : "No"));

        if (!$user) {
            // In production, don't reveal if user exists or not
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
            'password_reset',
            10 // 10 minutes expiry
        );

        // Send SMS
        $sent = $this->smsService->sendOtp($request->phone, $otp->code);

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
     * Send password reset link via email
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if user exists or not for security
            return $this->success(null, 'If this email is registered, you will receive a reset link');
        }

        // Generate OTP for email
        $otp = OtpCode::generate(
            $request->email,
            'email',
            'password_reset',
            30 // 30 minutes expiry for email
        );

        // TODO: Send email with reset link/code
        // For now, log the code in development
        if (config('app.env') === 'local') {
            return $this->success([
                'code' => $otp->code,
                'expiresAt' => $otp->expires_at->toIso8601String(),
            ], 'Reset code generated (dev mode)');
        }

        return $this->success(null, 'If this email is registered, you will receive a reset link');
    }

    /**
     * Verify OTP for password reset
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required_without:email|string',
            'email' => 'required_without:phone|email',
            'code' => 'required|string|min:6|max:7', // XXX-XXX or XXXXXX
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Normalize phone if provided
        $identifier = $request->phone
            ? SmsService::normalizePhone($request->phone)
            : $request->email;
        $type = $request->phone ? 'phone' : 'email';

        // Normalize code (remove dash if present)
        $normalizedCode = str_replace('-', '', $request->code);

        error_log("\n🔍 ===== VERIFY OTP =====");
        error_log("Identifier (original): " . ($request->phone ?? $request->email));
        error_log("Identifier (normalized): {$identifier}");
        error_log("Type: {$type}");
        error_log("Code received: {$request->code}");
        error_log("Normalized code: {$normalizedCode}");

        // Debug: Show all OTPs for this identifier
        $allOtps = OtpCode::where('identifier', $identifier)->get();
        error_log("All OTPs for this identifier: " . $allOtps->count());
        foreach ($allOtps as $o) {
            error_log("  - Code: {$o->code}, Purpose: {$o->purpose}, Type: {$o->type}, Expires: {$o->expires_at}, Verified: " . ($o->verified_at ?? 'null'));
        }

        /** @var OtpCode|null $otp */
        $otp = OtpCode::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', 'password_reset')
            ->valid()
            ->get()
            ->first(fn($o) => str_replace('-', '', $o->code) === $normalizedCode);

        error_log("OTP found: " . ($otp ? "Yes (ID: {$otp->id})" : "No"));
        error_log("==========================\n");

        if (!$otp) {
            return $this->error('Invalid or expired code', 400);
        }

        try {
            // Mark as verified but don't delete yet (needed for reset)
            $otp->markAsVerified();
            error_log("✅ OTP marked as verified");

            // Generate a reset token for the next step
            $resetToken = bin2hex(random_bytes(32));
            error_log("✅ Reset token generated: " . substr($resetToken, 0, 10) . "...");

            // Store reset token temporarily (you could also use cache)
            $otp->update([
                'metadata' => json_encode(['reset_token' => $resetToken]),
            ]);
            error_log("✅ Metadata saved");

            error_log("✅ SUCCESS - Returning resetToken");

            return $this->success([
                'resetToken' => $resetToken,
                'expiresAt' => now()->addMinutes(15)->toIso8601String(),
            ], 'Code verified successfully');
        } catch (\Exception $e) {
            error_log("❌ ERROR: " . $e->getMessage());
            return $this->error('Error verifying code: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password using verified OTP
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required_without:email|string',
            'email' => 'required_without:phone|email',
            'resetToken' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Normalize phone if provided
        $identifier = $request->phone
            ? SmsService::normalizePhone($request->phone)
            : $request->email;
        $type = $request->phone ? 'phone' : 'email';

        // Find the verified OTP with matching reset token
        /** @var OtpCode|null $otp */
        $otp = OtpCode::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', 'password_reset')
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now()->subMinutes(15)) // Reset token valid for 15 min after verification
            ->latest()
            ->first();

        if (!$otp) {
            return $this->error('Invalid or expired reset token', 400);
        }

        // Verify reset token
        $metadata = json_decode($otp->metadata ?? '{}', true);
        if (($metadata['reset_token'] ?? '') !== $request->resetToken) {
            return $this->error('Invalid reset token', 400);
        }

        // Find user (try multiple phone formats)
        /** @var User|null $user */
        $user = $type === 'phone'
            ? User::where('phone', $identifier)
                ->orWhere('phone', '+' . $identifier)
                ->orWhere('phone', $request->phone)
                ->first()
            : User::where('email', $identifier)->first();

        if (!$user) {
            return $this->error('User not found', 404);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the OTP
        $otp->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();
        $user->refreshTokens()->update(['revoked_at' => now()]);

        return $this->success(null, 'Password reset successfully. Please login with your new password.');
    }
}
