<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\ProofOfLocation;
use App\Services\PdfService;
use App\Services\ProofOfLocationService;
use App\Services\QrCodeService;
use App\Services\WebAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProofOfLocationController extends Controller
{
    public function __construct(
        protected ProofOfLocationService $proofService,
        protected PdfService $pdfService,
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

        $proof->recordDownload();

        // Use PdfService with correct templates based on document type
        if ($proof->isLocationPlan()) {
            return $this->pdfService->generateLocationPlanPdf($proof);
        }

        return $this->pdfService->generateProofOfResidencePdf($proof);
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
            'issuedAt' => $proof->issued_at->toIso8601String(),
            'expiresAt' => $proof->expires_at->toIso8601String(),
            'verificationUrl' => route('web.proof.verify', ['token' => $token]),
        ], 'Proof of location verified');
    }

    /**
     * Generate free location plan for individual users (V1)
     * No payment required - location plans are free for this version
     */
    public function generate(Request $request): JsonResponse
    {
        $addressId = $request->input('addressId') ?? $request->input('address_id');

        $validator = Validator::make([
            'addressId' => $addressId,
        ], [
            'addressId' => 'required|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        /** @var Address $address */
        $address = Address::find($addressId);
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Verify ownership
        if ($address->user_id !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        try {
            $proof = $this->proofService->generateFreeLocationPlan($user, $address);

            return $this->success([
                'document' => $this->proofService->formatProofForResponse($proof),
                'message' => 'Votre plan de localisation a été généré avec succès',
            ], 'Location plan generated');
        } catch (\Exception $e) {
            return $this->error('Failed to generate location plan: ' . $e->getMessage(), 500);
        }
    }
}
