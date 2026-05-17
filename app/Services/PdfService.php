<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProofOfLocation;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Generate PDF for Proof of Location / Proof of Residence
     */
    public function generateProofPdf(ProofOfLocation $proof): Response
    {
        $proof->load(['user', 'address.street']);

        $data = [
            'proof' => $proof,
            'user' => $proof->user,
            'address' => $proof->address,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $this->generateQrCodeDataUrl($proof->getVerificationUrl()),
        ];

        $view = $proof->isProofOfResidence()
            ? 'pdf.proof-of-residence'
            : 'pdf.location-plan';

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = sprintf(
            '%s_%s.pdf',
            $proof->document_number,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    /**
     * Generate PDF for Invoice
     */
    public function generateInvoicePdf(Invoice $invoice): Response
    {
        $invoice->load(['user', 'payment']);

        $data = [
            'invoice' => $invoice,
            'user' => $invoice->user,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $invoice->verification_code
                ? $this->generateQrCodeDataUrl(config('app.url') . '/verify/' . $invoice->verification_code)
                : null,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = sprintf(
            '%s_%s.pdf',
            $invoice->invoice_number,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    /**
     * Generate PDF for Receipt
     */
    public function generateReceiptPdf(Receipt $receipt): Response
    {
        $receipt->load(['user', 'payment', 'invoice']);

        $data = [
            'receipt' => $receipt,
            'user' => $receipt->user,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $this->generateQrCodeDataUrl($receipt->getVerificationUrl()),
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = sprintf(
            '%s_%s.pdf',
            $receipt->receipt_number,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }

    /**
     * Generate HTML response (for web viewing without download)
     */
    public function generateProofHtml(ProofOfLocation $proof): string
    {
        $proof->load(['user', 'address.street']);

        $data = [
            'proof' => $proof,
            'user' => $proof->user,
            'address' => $proof->address,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $this->generateQrCodeDataUrl($proof->getVerificationUrl()),
        ];

        $view = $proof->isProofOfResidence()
            ? 'pdf.proof-of-residence'
            : 'pdf.location-plan';

        return view($view, $data)->render();
    }

    /**
     * Get company information from config
     */
    protected function getCompanyInfo(): array
    {
        return [
            'name' => config('documents.company.name', 'Ket-Up Sarl'),
            'brand' => config('documents.company.brand', 'SomeWhere App'),
            'address' => config('documents.company.address'),
            'phone' => config('documents.company.phone'),
            'email' => config('documents.company.email'),
            'website' => config('documents.company.website'),
            'rccm' => config('documents.company.rccm'),
            'niu' => config('documents.company.niu'),
            'logo' => $this->getLogoDataUrl(),
        ];
    }

    /**
     * Get logo as base64 data URL
     */
    protected function getLogoDataUrl(): ?string
    {
        $logoPath = public_path(config('documents.pdf.logo_path', 'images/logo-somewhere.png'));

        if (!file_exists($logoPath)) {
            return null;
        }

        $logoContent = file_get_contents($logoPath);
        $logoMime = mime_content_type($logoPath);

        return 'data:' . $logoMime . ';base64,' . base64_encode($logoContent);
    }

    /**
     * Generate QR code as base64 data URL
     */
    protected function generateQrCodeDataUrl(string $data): string
    {
        $qrCodeService = app(QrCodeService::class);
        return $qrCodeService->generate($data);
    }
}
