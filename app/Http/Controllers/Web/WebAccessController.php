<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebAccessToken;
use App\Services\WebAccessService;
use Illuminate\Http\Request;

class WebAccessController extends Controller
{
    public function __construct(
        protected WebAccessService $webAccessService
    ) {}

    /**
     * Handle web access via QR code token
     */
    public function access(string $token, Request $request)
    {
        $webToken = $this->webAccessService->validateAndUseToken(
            $token,
            $request->ip(),
            $request->userAgent()
        );

        if (!$webToken) {
            return view('web.access.expired');
        }

        // Redirect based on token type
        return match ($webToken->type) {
            'proof_of_location' => $this->handleProofAccess($webToken),
            'invoice' => $this->handleInvoiceAccess($webToken),
            'kyc_status' => $this->handleKycAccess($webToken),
            'dashboard' => $this->handleDashboardAccess($webToken),
            default => view('web.access.invalid'),
        };
    }

    protected function handleProofAccess(WebAccessToken $token)
    {
        $resource = $this->webAccessService->getResourceFromToken($token);

        if (!$resource) {
            return view('web.proof.not-found');
        }

        return view('web.proof.show', [
            'proof' => $resource,
            'user' => $resource->user,
            'address' => $resource->address,
            'canDownload' => true,
        ]);
    }

    protected function handleInvoiceAccess(WebAccessToken $token)
    {
        $resource = $this->webAccessService->getResourceFromToken($token);

        if (!$resource) {
            return view('web.invoice.not-found');
        }

        return view('web.invoice.show', [
            'invoice' => $resource,
            'user' => $resource->user,
            'payment' => $resource->payment,
            'canDownload' => true,
        ]);
    }

    protected function handleKycAccess(WebAccessToken $token)
    {
        $resource = $this->webAccessService->getResourceFromToken($token);
        $user = $token->user;

        return view('web.kyc.status', [
            'kyc' => $resource,
            'user' => $user,
        ]);
    }

    protected function handleDashboardAccess(WebAccessToken $token)
    {
        $user = $token->user;
        $user->load([
            'settings',
            'kycVerification',
            'proofOfLocations' => function ($q) {
                $q->where('status', 'active')
                  ->where('expires_at', '>', now())
                  ->with('address')
                  ->latest()
                  ->limit(10);
            },
            'invoices' => function ($q) {
                $q->with('payment')
                  ->latest()
                  ->limit(10);
            },
            'payments' => function ($q) {
                $q->latest()->limit(10);
            },
        ]);

        return view('web.dashboard.index', [
            'user' => $user,
            'token' => $token,
        ]);
    }
}
