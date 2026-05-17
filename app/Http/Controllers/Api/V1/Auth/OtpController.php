<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\Auth\SendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Models\OtpCode;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        $otp = OtpCode::generate(
            $request->phone,
            'phone',
            'verification',
            10
        );

        // Send SMS
        $sent = $this->smsService->sendOtp($request->phone, $otp->code);

        if (!$sent && config('app.env') !== 'local') {
            return $this->error('Failed to send OTP', 500);
        }

        $response = [
            'expiresAt' => $otp->expires_at->toISOString(),
        ];

        // In development, include the code for testing
        if (config('app.env') === 'local') {
            $response['code'] = $otp->code;
        }

        return $this->success($response, 'OTP sent successfully');
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $normalizedCode = $request->normalizedCode();

        $otp = OtpCode::where('identifier', $request->phone)
            ->where('type', 'phone')
            ->valid()
            ->get()
            ->first(fn ($o) => str_replace('-', '', $o->code) === $normalizedCode);

        if (!$otp) {
            return $this->success(['valid' => false], 'Invalid or expired OTP');
        }

        $otp->markAsVerified();

        return $this->success(['valid' => true], 'OTP verified successfully');
    }
}
