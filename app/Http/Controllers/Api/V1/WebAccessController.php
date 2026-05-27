<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\KycService;
use App\Services\QrCodeService;
use App\Services\WebAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebAccessController extends Controller
{
    public function __construct(
        protected WebAccessService $webAccessService,
        protected QrCodeService $qrCodeService,
        protected KycService $kycService
    ) {}

    /**
     * Generate QR code for dashboard access
     */
    public function generateDashboardQr(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validityMinutes = min($request->get('validityMinutes', 60), 1440); // Max 24 hours

        $token = $this->webAccessService->createDashboardAccessToken($user, $validityMinutes);
        $qrCode = $this->qrCodeService->generatePngForWebAccess($token, 300);
        $qrData = $this->webAccessService->generateQrCodeData($token);

        return $this->success([
            'qrCode' => $qrCode,
            'qrData' => $qrData,
        ], 'Dashboard QR code generated');
    }

    /**
     * Generate QR code for KYC status viewing
     */
    public function generateKycStatusQr(): JsonResponse
    {
        $user = auth()->user();

        $token = $this->webAccessService->createKycAccessToken($user, 30);
        $qrCode = $this->qrCodeService->generatePngForWebAccess($token, 300);
        $qrData = $this->webAccessService->generateQrCodeData($token);

        return $this->success([
            'qrCode' => $qrCode,
            'qrData' => $qrData,
        ], 'KYC status QR code generated');
    }

    /**
     * Validate web access token (used by web frontend)
     */
    public function validateToken(string $token, Request $request): JsonResponse
    {
        $webToken = $this->webAccessService->validateAndUseToken(
            $token,
            $request->ip(),
            $request->userAgent()
        );

        if (!$webToken) {
            return $this->error('Invalid or expired access token', 401);
        }

        $resource = $this->webAccessService->getResourceFromToken($webToken);

        $responseData = [
            'type' => $webToken->type,
            'expiresAt' => $webToken->expires_at->toIso8601String(),
            'user' => [
                'id' => $webToken->user->id,
                'firstName' => $webToken->user->first_name,
                'lastName' => $webToken->user->last_name,
            ],
        ];

        // Add resource-specific data
        switch ($webToken->type) {
            case 'proof_of_location':
                if ($resource) {
                    $responseData['proof'] = [
                        'documentNumber' => $resource->document_number,
                        'status' => $resource->status,
                        'isActive' => $resource->isActive(),
                        'address' => [
                            'swAddress' => $resource->address->sw_address,
                            'displayName' => $resource->address->display_name,
                        ],
                        'issuedAt' => $resource->issued_at->toIso8601String(),
                        'expiresAt' => $resource->expires_at->toIso8601String(),
                    ];
                }
                break;

            case 'invoice':
                if ($resource) {
                    $responseData['invoice'] = [
                        'invoiceNumber' => $resource->invoice_number,
                        'description' => $resource->description,
                        'totalAmount' => $resource->total_amount,
                        'currency' => $resource->currency,
                        'invoiceDate' => $resource->invoice_date->toDateString(),
                        'isPaid' => $resource->isPaid(),
                    ];
                }
                break;

            case 'kyc_status':
                if ($resource) {
                    $responseData['kyc'] = $this->kycService->formatKycForResponse($resource);
                }
                break;

            case 'dashboard':
                $user = $webToken->user;
                $user->load(['settings', 'kycVerification', 'proofOfLocations' => function ($q) {
                    $q->where('status', 'active')->where('expires_at', '>', now())->latest()->limit(5);
                }, 'invoices' => function ($q) {
                    $q->latest()->limit(5);
                }]);

                $responseData['dashboard'] = [
                    'settings' => $user->settings,
                    'kycStatus' => $user->kycVerification ? [
                        'status' => $user->kycVerification->status,
                        'isApproved' => $user->kycVerification->isApproved(),
                    ] : null,
                    'activeProofsCount' => $user->proofOfLocations->count(),
                    'recentProofs' => $user->proofOfLocations->map(fn($p) => [
                        'documentNumber' => $p->document_number,
                        'status' => $p->status,
                        'expiresAt' => $p->expires_at->toIso8601String(),
                    ]),
                    'recentInvoices' => $user->invoices->map(fn($i) => [
                        'invoiceNumber' => $i->invoice_number,
                        'totalAmount' => $i->total_amount,
                        'currency' => $i->currency,
                        'invoiceDate' => $i->invoice_date->toDateString(),
                    ]),
                ];
                break;
        }

        return $this->success($responseData, 'Token validated');
    }
}
