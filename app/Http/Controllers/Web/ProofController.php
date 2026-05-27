<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProofOfLocation;
use App\Services\ProofOfLocationService;
use Illuminate\Http\Request;

class ProofController extends Controller
{
    public function __construct(
        protected ProofOfLocationService $proofService
    ) {}

    /**
     * Show proof of location via QR code token
     */
    public function show(string $token)
    {
        $proof = $this->proofService->findByQrToken($token);

        if (!$proof) {
            return view('web.proof.not-found');
        }

        return view('web.proof.show', [
            'proof' => $proof,
            'user' => $proof->user,
            'address' => $proof->address,
        ]);
    }

    /**
     * Verify proof of location
     */
    public function verify(string $token)
    {
        $proof = $this->proofService->findByQrToken($token);

        if (!$proof) {
            return response()->json([
                'valid' => false,
                'message' => 'Proof of location not found',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'isActive' => $proof->isActive(),
            'isExpired' => $proof->isExpired(),
            'documentNumber' => $proof->document_number,
            'holder' => [
                'firstName' => $proof->user->first_name,
                'lastName' => $proof->user->last_name,
            ],
            'address' => [
                'swAddress' => $proof->address->sw_address,
                'displayName' => $proof->address->display_name,
            ],
            'issuedAt' => $proof->issued_at->toIso8601String(),
            'expiresAt' => $proof->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Download proof PDF via token
     */
    public function download(string $token)
    {
        $proof = $this->proofService->findByQrToken($token);

        if (!$proof) {
            abort(404, 'Proof of location not found');
        }

        if (!$proof->isActive()) {
            abort(410, 'This proof of location has expired');
        }

        return $this->proofService->download($proof);
    }
}
