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
     * Generate proof of location after successful payment
     */
    public function generateAfterPayment(Payment $payment): ProofOfLocation
    {
        if (!$payment->isSuccessful()) {
            throw new \InvalidArgumentException('Payment must be successful to generate proof');
        }

        $user = $payment->user;
        $address = $payment->address;

        if (!$address) {
            throw new \InvalidArgumentException('Payment must have an associated address');
        }

        // Check if address is verified
        if ($address->verification_status !== 'approved') {
            throw new \InvalidArgumentException('Address must be verified to generate proof');
        }

        // Create proof of location record
        $proof = ProofOfLocation::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'payment_id' => $payment->id,
            'document_number' => ProofOfLocation::generateDocumentNumber($user, $address),
            'file_path' => '', // Will be set after PDF generation
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => now()->addMonths($this->getValidityMonths()),
        ]);

        // Generate PDF
        $filePath = $this->generatePdf($proof);
        $proof->update(['file_path' => $filePath]);

        // Update user settings (for backward compatibility)
        $settings = $user->getOrCreateSettings();
        $settings->update([
            'proof_of_residence' => $filePath,
            'proof_of_residence_date' => now(),
        ]);

        // Generate invoice
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
            'issuedAt' => $proof->issued_at->toISOString(),
            'expiresAt' => $proof->expires_at->toISOString(),
            'daysUntilExpiry' => $proof->expires_at->diffInDays(now()),
            'downloadUrl' => route('api.proof-of-location.download', ['id' => $proof->id]),
            'webUrl' => $proof->getWebUrl(),
            'qrCodeData' => $proof->getQrCodeData(),
            'downloadCount' => $proof->download_count,
            'qrScanCount' => $proof->qr_scan_count,
            'createdAt' => $proof->created_at->toISOString(),
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
