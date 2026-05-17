<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ProofOfResidenceService
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function generate(User $user, Address $address): array
    {
        $documentNumber = $this->generateDocumentNumber($user, $address);
        $qrCode = $this->qrCodeService->generateSvgForAddress($address, 100);

        $data = [
            'user' => $user,
            'address' => $address,
            'generated_at' => now(),
            'document_number' => $documentNumber,
            'qr_code' => $qrCode,
        ];

        $pdf = Pdf::loadView('pdf.proof-of-residence', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = "por_{$user->id}_{$address->id}_" . time() . '.pdf';
        $path = $filename;

        Storage::disk('proofs')->put($path, $pdf->output());

        // Update user settings
        $settings = $user->getOrCreateSettings();
        $settings->update([
            'proof_of_residence' => $path,
            'proof_of_residence_date' => now(),
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
            'document_number' => $documentNumber,
            'generated_at' => now()->toISOString(),
        ];
    }

    public function download(string $path)
    {
        if (!Storage::disk('proofs')->exists($path)) {
            return null;
        }

        return Storage::disk('proofs')->download($path);
    }

    public function getUrl(string $path): ?string
    {
        if (!Storage::disk('proofs')->exists($path)) {
            return null;
        }

        // Generate a temporary signed URL or return path
        return route('api.proof-of-residence.download', ['path' => $path]);
    }

    private function generateDocumentNumber(User $user, Address $address): string
    {
        return sprintf(
            'SW-POR-%d-%d-%s',
            $user->id,
            $address->id,
            strtoupper(substr(md5(now()->timestamp . $user->id), 0, 8))
        );
    }
}
