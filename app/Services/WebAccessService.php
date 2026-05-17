<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProofOfLocation;
use App\Models\User;
use App\Models\WebAccessToken;

class WebAccessService
{
    /**
     * Create access token for proof of location
     */
    public function createProofAccessToken(
        User $user,
        ProofOfLocation $proof,
        int $validityMinutes = 60
    ): WebAccessToken {
        return WebAccessToken::createForUser(
            user: $user,
            type: 'proof_of_location',
            resourceId: $proof->id,
            validityMinutes: $validityMinutes,
            maxUsage: -1 // Unlimited usage within validity period
        );
    }

    /**
     * Create access token for invoice
     */
    public function createInvoiceAccessToken(
        User $user,
        Invoice $invoice,
        int $validityMinutes = 60
    ): WebAccessToken {
        return WebAccessToken::createForUser(
            user: $user,
            type: 'invoice',
            resourceId: $invoice->id,
            validityMinutes: $validityMinutes,
            maxUsage: -1
        );
    }

    /**
     * Create access token for KYC status viewing
     */
    public function createKycAccessToken(
        User $user,
        int $validityMinutes = 30
    ): WebAccessToken {
        return WebAccessToken::createForUser(
            user: $user,
            type: 'kyc_status',
            resourceId: null,
            validityMinutes: $validityMinutes,
            maxUsage: -1
        );
    }

    /**
     * Create access token for user dashboard
     */
    public function createDashboardAccessToken(
        User $user,
        int $validityMinutes = 60
    ): WebAccessToken {
        return WebAccessToken::createForUser(
            user: $user,
            type: 'dashboard',
            resourceId: null,
            validityMinutes: $validityMinutes,
            maxUsage: -1
        );
    }

    /**
     * Validate and use access token
     */
    public function validateAndUseToken(
        string $token,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ?WebAccessToken {
        $webToken = WebAccessToken::findValidToken($token);

        if (!$webToken) {
            return null;
        }

        $webToken->use($ipAddress, $userAgent);

        return $webToken;
    }

    /**
     * Get resource from access token
     */
    public function getResourceFromToken(WebAccessToken $token): mixed
    {
        return match ($token->type) {
            'proof_of_location' => ProofOfLocation::with(['user', 'address'])->find($token->resource_id),
            'invoice' => Invoice::with(['user', 'payment'])->find($token->resource_id),
            'kyc_status' => $token->user->kycVerification,
            'dashboard' => $token->user,
            default => null,
        };
    }

    /**
     * Generate QR code data for web access
     */
    public function generateQrCodeData(WebAccessToken $token): array
    {
        $baseUrl = config('app.url');
        $webUrl = "{$baseUrl}/web/access/{$token->token}";

        return [
            'token' => $token->token,
            'url' => $webUrl,
            'type' => $token->type,
            'expiresAt' => $token->expires_at->toISOString(),
            'validityMinutes' => $token->expires_at->diffInMinutes(now()),
        ];
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return WebAccessToken::where('expires_at', '<', now())->delete();
    }
}
