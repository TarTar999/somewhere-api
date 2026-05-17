<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FapshiService
{
    protected string $baseUrl;
    protected string $apiUser;
    protected string $apiKey;
    protected bool $isSandbox;

    public function __construct()
    {
        $this->apiUser = config('services.fapshi.api_user');
        $this->apiKey = config('services.fapshi.api_key');
        $this->isSandbox = str_starts_with($this->apiKey, 'FAK_TEST_');
        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.fapshi.com'
            : 'https://live.fapshi.com';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiUser) && !empty($this->apiKey);
    }

    /**
     * Initiate a payment with Fapshi hosted checkout page
     */
    public function initiatePay(
        int $amount,
        string $email,
        ?string $redirectUrl = null,
        ?string $userId = null,
        ?string $externalId = null,
        ?string $message = null
    ): array {
        $payload = [
            'amount' => $amount,
            'email' => $email,
        ];

        if ($redirectUrl) {
            $payload['redirectUrl'] = $redirectUrl;
        }
        if ($userId) {
            $payload['userId'] = $userId;
        }
        if ($externalId) {
            $payload['externalId'] = $externalId;
        }
        if ($message) {
            $payload['message'] = $message;
        }

        return $this->request('POST', '/initiate-pay', $payload);
    }

    /**
     * Direct payment to a phone number (Mobile Money / Orange Money)
     */
    public function directPay(
        int $amount,
        string $phone,
        string $medium = 'mobile money',
        ?string $externalId = null,
        ?string $userId = null,
        ?string $message = null
    ): array {
        $payload = [
            'amount' => $amount,
            'phone' => $phone,
            'medium' => $medium,
        ];

        if ($externalId) {
            $payload['externalId'] = $externalId;
        }
        if ($userId) {
            $payload['userId'] = $userId;
        }
        if ($message) {
            $payload['message'] = $message;
        }

        return $this->request('POST', '/direct-pay', $payload);
    }

    /**
     * Get payment status by transaction ID
     */
    public function getPaymentStatus(string $transactionId): array
    {
        return $this->request('GET', "/payment-status/{$transactionId}");
    }

    /**
     * Expire a pending payment
     */
    public function expirePay(string $transactionId): array
    {
        return $this->request('POST', '/expire-pay', [
            'transId' => $transactionId,
        ]);
    }

    /**
     * Payout to a phone number
     */
    public function payout(int $amount, string $phone, ?string $message = null): array
    {
        $payload = [
            'amount' => $amount,
            'phone' => $phone,
        ];

        if ($message) {
            $payload['message'] = $message;
        }

        return $this->request('POST', '/payout', $payload);
    }

    /**
     * Get account balance
     */
    public function getBalance(): array
    {
        return $this->request('GET', '/balance');
    }

    /**
     * Search transactions
     */
    public function searchTransactions(array $filters = []): array
    {
        return $this->request('GET', '/transactions', $filters);
    }

    /**
     * Create a payment for proof of location
     */
    public function createProofOfLocationPayment(
        User $user,
        Address $address,
        string $redirectUrl
    ): Payment {
        $amount = (int) config('services.fapshi.proof_of_location_price', 1000);

        $payment = Payment::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'type' => 'proof_of_location',
            'amount' => $amount,
            'currency' => 'XAF',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
        ]);

        try {
            $response = $this->initiatePay(
                amount: $amount,
                email: $user->email,
                redirectUrl: $redirectUrl,
                userId: (string) $user->id,
                externalId: $payment->external_id,
                message: "Proof of Location - {$address->sw_address}"
            );

            if (isset($response['transId'])) {
                $payment->update([
                    'transaction_id' => $response['transId'],
                    'payment_link' => $response['link'] ?? null,
                    'fapshi_response' => $response,
                ]);
            } else {
                $payment->markAsFailed('Failed to create payment', $response);
            }
        } catch (\Exception $e) {
            Log::error('Fapshi payment creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
            $payment->markAsFailed($e->getMessage());
        }

        return $payment->fresh();
    }

    /**
     * Create a direct payment for proof of location (Mobile Money)
     */
    public function createDirectProofOfLocationPayment(
        User $user,
        Address $address,
        string $phone
    ): Payment {
        $amount = (int) config('services.fapshi.proof_of_location_price', 1000);

        $payment = Payment::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'type' => 'proof_of_location',
            'amount' => $amount,
            'currency' => 'XAF',
            'status' => 'pending',
            'phone' => $phone,
            'medium' => 'mobile money',
            'expires_at' => now()->addHours(24),
        ]);

        try {
            $response = $this->directPay(
                amount: $amount,
                phone: $phone,
                externalId: $payment->external_id,
                userId: (string) $user->id,
                message: "Proof of Location - {$address->sw_address}"
            );

            if (isset($response['transId'])) {
                $payment->update([
                    'transaction_id' => $response['transId'],
                    'fapshi_response' => $response,
                ]);
            } else {
                $payment->markAsFailed('Failed to create payment', $response);
            }
        } catch (\Exception $e) {
            Log::error('Fapshi direct payment creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
            $payment->markAsFailed($e->getMessage());
        }

        return $payment->fresh();
    }

    /**
     * Process webhook callback from Fapshi
     */
    public function processWebhook(array $payload): ?Payment
    {
        $transactionId = $payload['transId'] ?? null;

        if (!$transactionId) {
            Log::warning('Fapshi webhook received without transId', $payload);
            return null;
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            Log::warning('Fapshi webhook received for unknown transaction', [
                'transId' => $transactionId,
                'payload' => $payload,
            ]);
            return null;
        }

        $status = strtoupper($payload['status'] ?? '');

        switch ($status) {
            case 'SUCCESSFUL':
                $payment->markAsSuccessful($payload);
                break;

            case 'FAILED':
                $payment->markAsFailed($payload['reason'] ?? 'Payment failed', $payload);
                break;

            case 'EXPIRED':
                $payment->markAsExpired();
                break;

            default:
                Log::info('Fapshi webhook received with unknown status', [
                    'status' => $status,
                    'transId' => $transactionId,
                ]);
        }

        return $payment->fresh();
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'apiuser' => $this->apiUser,
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        try {
            $response = match (strtoupper($method)) {
                'GET' => Http::withHeaders($headers)->get($url, $data),
                'POST' => Http::withHeaders($headers)->post($url, $data),
                'PUT' => Http::withHeaders($headers)->put($url, $data),
                'DELETE' => Http::withHeaders($headers)->delete($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error('Fapshi API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'endpoint' => $endpoint,
            ]);

            return [
                'error' => true,
                'message' => $response->json('message') ?? 'API request failed',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Fapshi API exception', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
