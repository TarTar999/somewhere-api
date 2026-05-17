<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $baseUrl = 'https://smsvas.com/bulk/public/index.php/api/v1/sendsms';
    protected ?string $user;
    protected ?string $password;
    protected ?string $senderId;

    public function __construct()
    {
        $this->user = config('services.smsvas.user');
        $this->password = config('services.smsvas.password');
        $this->senderId = config('services.smsvas.sender_id');
    }

    public function sendOtp(string $phone, string $code): bool
    {
        $message = "{$code}";

        return $this->send($phone, $message);
    }

    public function send(string $to, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('SMSVAS not configured. SMS not sent.', [
                'to' => $to,
                'message' => $message,
            ]);

            // In development, log the message instead
            if (config('app.env') === 'local') {
                Log::info('SMS would be sent (dev mode):', [
                    'to' => $to,
                    'message' => $message,
                ]);
                return true;
            }

            return false;
        }

        try {
            // Format phone number: remove +, spaces, keep last 9 digits and add 237 prefix
            $cleanPhone = preg_replace('/[^0-9]/', '', $to); // Remove all non-numeric characters
            $formattedPhone = '237' . substr($cleanPhone, -9);

            $response = Http::get($this->baseUrl, [
                'user' => $this->user,
                'password' => $this->password,
                'senderid' => $this->senderId,
                'sms' => $message,
                'mobiles' => $formattedPhone,
            ]);

            // Afficher la réponse dans le terminal
            $responseData = $response->json();
            error_log("\n📱 ===== SMS API RESPONSE =====");
            error_log("To: {$formattedPhone}");
            error_log("Message: {$message}");
            error_log("Status: " . $response->status());
            error_log("Response: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            error_log("==============================\n");

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'to' => $formattedPhone,
                    'response' => $response->body(),
                ]);
                return true;
            }

            Log::error('Failed to send SMS - API error', [
                'to' => $formattedPhone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->user) && !empty($this->password) && !empty($this->senderId);
    }

    /**
     * Normalize phone number to format: 237XXXXXXXXX
     */
    public static function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 237, keep as is, otherwise add 237 prefix
        if (str_starts_with($cleanPhone, '237')) {
            return $cleanPhone;
        }

        // Take last 9 digits and add 237
        return '237' . substr($cleanPhone, -9);
    }
}
