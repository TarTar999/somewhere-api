<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProofOfLocation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ProofOfLocationService
{
    protected QrCodeService $qrCodeService;
    protected InvoiceService $invoiceService;

    public function __construct(QrCodeService $qrCodeService, InvoiceService $invoiceService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate document after successful payment
     */
    public function generateAfterPayment(Payment $payment, ?string $documentType = null): ProofOfLocation
    {
        if (!$payment->isSuccessful()) {
            throw new \InvalidArgumentException('Payment must be successful to generate document');
        }

        $user = $payment->user;
        $address = $payment->address;

        if (!$address) {
            throw new \InvalidArgumentException('Payment must have an associated address');
        }

        // Determine document type from payment type if not specified
        $documentType = $documentType ?? $payment->type;
        if ($documentType === 'proof_of_location') {
            $documentType = ProofOfLocation::TYPE_LOCATION_PLAN;
        }

        // Check if address is verified (only for proof_of_residence)
        if ($documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE && $address->verification_status !== 'approved') {
            throw new \InvalidArgumentException('Address must be verified to generate proof of residence');
        }

        // Get the price based on document type
        $price = ProofOfLocation::getPrice($documentType);

        // Create document record
        $proof = ProofOfLocation::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'payment_id' => $payment->id,
            'document_type' => $documentType,
            'document_number' => ProofOfLocation::generateDocumentNumber($user, $address, $documentType),
            'verification_code' => ProofOfLocation::generateVerificationCode(),
            'price' => $price,
            'file_path' => '', // Will be set after PDF generation
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => now()->addMonths($this->getValidityMonths()),
        ]);

        // Generate PDF
        $filePath = $this->generatePdf($proof);
        $proof->update(['file_path' => $filePath]);

        // Update user settings (for backward compatibility, only for proof_of_residence)
        if ($documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE) {
            $settings = $user->getOrCreateSettings();
            $settings->update([
                'proof_of_residence' => $filePath,
                'proof_of_residence_date' => now(),
            ]);
        }

        // Generate invoice and receipt
        $this->invoiceService->createFromPayment($payment);

        return $proof->fresh();
    }

    /**
     * Generate PDF document
     */
    public function generatePdf(ProofOfLocation $proof): string
    {
        $proof->load(['user', 'address.street']);

        $qrCode = $this->qrCodeService->generateSvgForProofOfLocation($proof, 100);

        $data = [
            'proof' => $proof,
            'user' => $proof->user,
            'address' => $proof->address,
            'qr_code' => $qrCode,
            'generated_at' => now(),
            'valid_until' => $proof->expires_at,
            'document_number' => $proof->document_number,
        ];

        $pdf = Pdf::loadView('pdf.proof-of-location', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = "pol_{$proof->user_id}_{$proof->address_id}_{$proof->id}.pdf";
        $path = $filename;

        Storage::disk('proofs')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Download proof PDF
     */
    public function download(ProofOfLocation $proof)
    {
        if (!Storage::disk('proofs')->exists($proof->file_path)) {
            // Regenerate if file is missing
            $this->generatePdf($proof);
            $proof->refresh();
        }

        $proof->recordDownload();

        return Storage::disk('proofs')->download(
            $proof->file_path,
            "proof_of_location_{$proof->document_number}.pdf"
        );
    }

    /**
     * Get user's active proofs of location
     */
    public function getActiveProofs(User $user)
    {
        return $user->proofOfLocations()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with(['address', 'payment'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get all user's proofs of location
     */
    public function getAllProofs(User $user, int $perPage = 15)
    {
        return $user->proofOfLocations()
            ->with(['address', 'payment'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Check and expire old proofs
     */
    public function expireOldProofs(): int
    {
        return ProofOfLocation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Find proof by QR code token
     */
    public function findByQrToken(string $token): ?ProofOfLocation
    {
        $proof = ProofOfLocation::where('qr_code_token', $token)
            ->with(['user', 'address'])
            ->first();

        if ($proof) {
            $proof->recordQrScan();
        }

        return $proof;
    }

    /**
     * Format proof for API response
     */
    public function formatProofForResponse(ProofOfLocation $proof): array
    {
        return [
            'id' => $proof->id,
            'documentNumber' => $proof->document_number,
            'status' => $proof->status,
            'isActive' => $proof->isActive(),
            'isExpired' => $proof->isExpired(),
            'address' => [
                'id' => $proof->address_id,
                'swAddress' => $proof->address->sw_address ?? null,
                'displayName' => $proof->address->display_name ?? null,
            ],
            'issuedAt' => $proof->issued_at->toIso8601String(),
            'expiresAt' => $proof->expires_at->toIso8601String(),
            'daysUntilExpiry' => $proof->expires_at->diffInDays(now()),
            'downloadUrl' => route('api.proof-of-location.download', ['id' => $proof->id]),
            'webUrl' => $proof->getWebUrl(),
            'qrCodeData' => $proof->getQrCodeData(),
            'downloadCount' => $proof->download_count,
            'qrScanCount' => $proof->qr_scan_count,
            'createdAt' => $proof->created_at->toIso8601String(),
        ];
    }

    /**
     * Get validity period in months from config
     */
    protected function getValidityMonths(): int
    {
        return (int) config('app.proof_of_location_validity_months', 3);
    }
}
