<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ProofOfLocation;
use App\Services\ProofOfLocationService;
use App\Services\QrCodeService;
use App\Services\WebAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProofOfLocationController extends Controller
{
    public function __construct(
        protected ProofOfLocationService $proofService,
        protected QrCodeService $qrCodeService,
        protected WebAccessService $webAccessService
    ) {}

    /**
     * List user's proofs of location
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $proofs = $this->proofService->getAllProofs($user, $request->get('perPage', 15));

        $data = $proofs->map(fn($p) => $this->proofService->formatProofForResponse($p));

        return $this->paginated($proofs->setCollection($data), 'Proofs of location retrieved');
    }

    /**
     * Get active proofs only
     */
    public function getActive(): JsonResponse
    {
        $user = auth()->user();
        $proofs = $this->proofService->getActiveProofs($user);

        $data = $proofs->map(fn($p) => $this->proofService->formatProofForResponse($p));

        return $this->success($data, 'Active proofs retrieved');
    }

    /**
     * Get single proof details
     */
    public function show(int $id): JsonResponse
    {
        $proof = ProofOfLocation::with(['address', 'payment', 'user'])->find($id);

        if (!$proof) {
            return $this->error('Proof of location not found', 404);
        }

        if ($proof->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success(
            $this->proofService->formatProofForResponse($proof),
            'Proof of location retrieved'
        );
    }

    /**
     * Download proof PDF
     */
    public function download(int $id)
    {
        $proof = ProofOfLocation::find($id);

        if (!$proof) {
            return response()->json(['message' => 'Proof of location not found'], 404);
        }

        if ($proof->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $this->proofService->download($proof);
    }

    /**
     * Generate QR code for web access
     */
    public function generateWebAccessQr(int $id): JsonResponse
    {
        $proof = ProofOfLocation::find($id);

        if (!$proof) {
            return $this->error('Proof of location not found', 404);
        }

        if ($proof->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // Create web access token
        $token = $this->webAccessService->createProofAccessToken(
            auth()->user(),
            $proof,
            60 // 60 minutes validity
        );

        // Generate QR code
        $qrCode = $this->qrCodeService->generatePngForWebAccess($token, 300);
        $qrData = $this->webAccessService->generateQrCodeData($token);

        return $this->success([
            'qrCode' => $qrCode,
            'qrData' => $qrData,
            'proof' => $this->proofService->formatProofForResponse($proof),
        ], 'QR code generated');
    }

    /**
     * Get proof by QR token (public endpoint)
     */
    public function showByQrToken(string $token): JsonResponse
    {
        $proof = $this->proofService->findByQrToken($token);

        if (!$proof) {
            return $this->error('Invalid or expired QR code', 404);
        }

        // Return limited public information
        return $this->success([
            'documentNumber' => $proof->document_number,
            'status' => $proof->status,
            'isActive' => $proof->isActive(),
            'isExpired' => $proof->isExpired(),
            'holder' => [
                'firstName' => $proof->user->first_name,
                'lastName' => $proof->user->last_name,
            ],
            'address' => [
                'swAddress' => $proof->address->sw_address,
                'displayName' => $proof->address->display_name,
                'quarter' => $proof->address->quarter,
                'subQuarter' => $proof->address->sub_quarter,
            ],
            'issuedAt' => $proof->issued_at->toISOString(),
            'expiresAt' => $proof->expires_at->toISOString(),
            'verificationUrl' => route('web.proof.verify', ['token' => $token]),
        ], 'Proof of location verified');
    }
}
