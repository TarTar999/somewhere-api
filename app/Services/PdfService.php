<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProofOfLocation;
use App\Models\Receipt;
use App\Models\Address;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfService
{
    protected string $mapboxToken;
    protected ?DocumentDownloadService $downloadService;

    public function __construct(?DocumentDownloadService $downloadService = null)
    {
        $this->mapboxToken = config('services.mapbox.token', '');
        $this->downloadService = $downloadService ?? app(DocumentDownloadService::class);
    }

    /**
     * Generate PDF for Location Plan
     */
    public function generateLocationPlanPdf(ProofOfLocation $proof): Response
    {
        // Load address with street and itinerary data
        $proof->load(['user', 'address.street', 'address.itineraryStreet', 'payment']);

        $data = $this->prepareProofData($proof);
        $data['documentTitle'] = 'PLAN DE LOCALISATION';

        $pdf = Pdf::loadView('pdf.location-plan', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($proof->document_number . '.pdf');
    }

    /**
     * Generate PDF for Proof of Residence
     */
    public function generateProofOfResidencePdf(ProofOfLocation $proof): Response
    {
        // Load address with street and itinerary data
        $proof->load(['user', 'address.street', 'address.itineraryStreet', 'payment']);

        $data = $this->prepareProofData($proof);
        $data['documentTitle'] = 'ATTESTATION DE RESIDENCE';

        $pdf = Pdf::loadView('pdf.proof-of-residence', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($proof->document_number . '.pdf');
    }

    /**
     * Generate unified PDF for Proof (Location Plan or Residence)
     */
    public function generateProofPdf(ProofOfLocation $proof): Response
    {
        if ($proof->isProofOfResidence()) {
            return $this->generateProofOfResidencePdf($proof);
        }
        return $this->generateLocationPlanPdf($proof);
    }

    /**
     * Prepare common data for proof documents
     */
    protected function prepareProofData(ProofOfLocation $proof): array
    {
        $address = $proof->address;
        $user = $proof->user;

        // Load itinerary street if not loaded
        if (!$address->relationLoaded('itineraryStreet')) {
            $address->load('itineraryStreet');
        }

        // Generate map image (includes itinerary path if available)
        $mapUrl = $this->generateMapImage($address);
        $mapBase64 = $this->fetchImageAsBase64($mapUrl);

        // Calculate reliability score
        $score = $this->calculateReliabilityScore($proof);

        // Generate document hash
        $documentHash = $this->generateDocumentHash($proof);

        // Prepare itinerary data for template
        $itineraryData = null;
        if ($address->hasItinerary()) {
            $itinerary = $address->itinerary;
            $itineraryData = [
                'pointsCount' => count($itinerary),
                'distance' => $address->itinerary_distance,
                'distanceFormatted' => $address->itinerary_distance
                    ? ($address->itinerary_distance >= 1000
                        ? number_format($address->itinerary_distance / 1000, 2) . ' km'
                        : $address->itinerary_distance . ' m')
                    : null,
                'description' => $address->itinerary_description,
                'destinationStreet' => $address->itineraryStreet
                    ? $address->itineraryStreet->display_name
                    : null,
            ];
        }

        return [
            'proof' => $proof,
            'user' => $user,
            'address' => $address,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $this->generateQrCodeDataUrl($proof->getVerificationUrl()),
            'mapImage' => $mapBase64,
            'score' => $score,
            'documentHash' => $documentHash,
            'itinerary' => $itineraryData,
            'userSignature' => $this->getSignatureDataUrl($address->signature),
            'dates' => [
                'today' => now()->format('d/m/Y'),
                'issued' => $proof->issued_at->format('d/m/Y'),
                'expires' => $proof->expires_at->format('d/m/Y'),
            ],
        ];
    }

    /**
     * Generate PDF for Invoice
     */
    public function generateInvoicePdf(Invoice $invoice): Response
    {
        $invoice->load(['user', 'payment.address', 'payment.proofOfLocation']);

        // Get signature from address (via payment or proof of location)
        $signature = null;
        if ($invoice->payment && $invoice->payment->address) {
            $signature = $invoice->payment->address->signature;
        } elseif ($invoice->payment && $invoice->payment->proofOfLocation && $invoice->payment->proofOfLocation->address) {
            $signature = $invoice->payment->proofOfLocation->address->signature;
        }

        $data = [
            'invoice' => $invoice,
            'user' => $invoice->user,
            'payment' => $invoice->payment,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $invoice->verification_code
                ? $this->generateQrCodeDataUrl(config('app.url') . '/verify/invoice/' . $invoice->verification_code)
                : null,
            'userSignature' => $this->getSignatureDataUrl($signature),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    /**
     * Generate PDF for Receipt
     */
    public function generateReceiptPdf(Receipt $receipt): Response
    {
        $receipt->load(['user', 'payment', 'invoice']);

        // Get signature from address (via payment)
        $signature = null;
        if ($receipt->payment && $receipt->payment->address) {
            $signature = $receipt->payment->address->signature;
        }

        $data = [
            'receipt' => $receipt,
            'user' => $receipt->user,
            'company' => $this->getCompanyInfo(),
            'qrCodeUrl' => $this->generateQrCodeDataUrl($receipt->getVerificationUrl()),
            'userSignature' => $this->getSignatureDataUrl($signature),
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($receipt->receipt_number . '.pdf');
    }

    /**
     * Generate Mapbox static map image URL with optional itinerary path
     */
    protected function generateMapImage(Address $address): string
    {
        if (empty($this->mapboxToken)) {
            return '';
        }

        $lat = $address->latitude;
        $lon = $address->longitude;

        if (!$lat || !$lon) {
            return '';
        }

        $overlays = [];

        // Add itinerary path if available
        if ($address->hasItinerary()) {
            $pathOverlay = $this->generateItineraryPath($address);
            if ($pathOverlay) {
                $overlays[] = $pathOverlay;
            }
        }

        // Add address marker (red pin)
        $overlays[] = "pin-l+ff0000({$lon},{$lat})";

        // Add itinerary start marker if different from address
        if ($address->hasItinerary()) {
            $itinerary = $address->itinerary;
            if (count($itinerary) > 0) {
                $startPoint = $itinerary[0];
                // Only add start marker if it's not too close to the address
                $distance = $this->calculateDistance(
                    $lat, $lon,
                    $startPoint['lat'], $startPoint['lng']
                );
                if ($distance > 20) { // More than 20 meters away
                    $overlays[] = "pin-s+4ade80({$startPoint['lng']},{$startPoint['lat']})";
                }
            }
        }

        $overlayString = implode(',', $overlays);
        $zoom = 14;

        // Calculate bounds if itinerary exists to fit all points
        if ($address->hasItinerary()) {
            $bounds = $this->calculateBounds($address);
            if ($bounds) {
                // Use auto zoom with bounding box
                return sprintf(
                    'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/%s/[%s,%s,%s,%s]/650x450@2x?access_token=%s&padding=50',
                    $overlayString,
                    $bounds['minLng'],
                    $bounds['minLat'],
                    $bounds['maxLng'],
                    $bounds['maxLat'],
                    $this->mapboxToken
                );
            }
        }

        return sprintf(
            'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/%s/%s,%s,%s/650x450@2x?access_token=%s',
            $overlayString,
            $lon,
            $lat,
            $zoom,
            $this->mapboxToken
        );
    }

    /**
     * Generate Mapbox path overlay string for itinerary
     * Format: path-{strokeWidth}+{strokeColor}-{strokeOpacity}({coordinates})
     * Coordinates format: lng,lat pairs separated by commas
     */
    protected function generateItineraryPath(Address $address): ?string
    {
        if (!$address->hasItinerary() || count($address->itinerary) < 1) {
            return null;
        }

        $addressLat = (float) $address->latitude;
        $addressLng = (float) $address->longitude;

        // Store itinerary in local variable (PHP 8.4 doesn't allow indirect modification of casted properties)
        $itinerary = $address->itinerary;

        // Build coordinate pairs for the path
        $coordinates = [];
        foreach ($itinerary as $point) {
            $coordinates[] = round($point['lng'], 6) . ',' . round($point['lat'], 6);
        }

        // Always add the address point at the end to complete the path
        $lastPoint = end($itinerary);
        $distanceToEnd = $this->calculateDistance(
            $lastPoint['lat'], $lastPoint['lng'],
            $addressLat, $addressLng
        );

        // Add address point if it's more than 3 meters from the last itinerary point
        if ($distanceToEnd > 3) {
            $coordinates[] = round($addressLng, 6) . ',' . round($addressLat, 6);
        }

        // Need at least 2 points to draw a path
        if (count($coordinates) < 2) {
            // If only 1 itinerary point, add the address as endpoint
            $coordinates[] = round($addressLng, 6) . ',' . round($addressLat, 6);
        }

        $pathCoords = implode(',', $coordinates);

        // Path format: path-{strokeWidth}+{strokeColor}-{strokeOpacity}({coords})
        // Using green color (#22c55e), 5px width for better visibility
        return "path-5+22c55e({$pathCoords})";
    }

    /**
     * Calculate bounds for map to include address and all itinerary points
     */
    protected function calculateBounds(Address $address): ?array
    {
        $points = [];

        // Add address point
        $points[] = ['lat' => (float) $address->latitude, 'lng' => (float) $address->longitude];

        // Add itinerary points
        if ($address->hasItinerary()) {
            $itinerary = $address->itinerary;
            foreach ($itinerary as $point) {
                $points[] = ['lat' => (float) $point['lat'], 'lng' => (float) $point['lng']];
            }
        }

        if (count($points) < 2) {
            return null;
        }

        $minLat = min(array_column($points, 'lat'));
        $maxLat = max(array_column($points, 'lat'));
        $minLng = min(array_column($points, 'lng'));
        $maxLng = max(array_column($points, 'lng'));

        // Add padding
        $latPadding = ($maxLat - $minLat) * 0.1;
        $lngPadding = ($maxLng - $minLng) * 0.1;

        return [
            'minLat' => $minLat - $latPadding,
            'maxLat' => $maxLat + $latPadding,
            'minLng' => $minLng - $lngPadding,
            'maxLng' => $maxLng + $lngPadding,
        ];
    }

    /**
     * Calculate distance between two points (in meters)
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Fetch image and convert to base64
     */
    protected function fetchImageAsBase64(string $url, string $mimeType = 'image/png'): string
    {
        if (empty($url)) {
            return '';
        }

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                return "data:{$mimeType};base64," . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch image: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Calculate reliability score for location
     */
    protected function calculateReliabilityScore(ProofOfLocation $proof): int
    {
        $score = 0;
        $user = $proof->user;
        $address = $proof->address;

        // Phone verification
        if ($user->phone) {
            $score += 15;
        }

        // GPS coordinates
        if ($address->latitude && $address->longitude) {
            $score += 25;
        }

        // Address verification status
        if ($address->verification_status === 'approved') {
            $score += 25;
        }

        // User identity (NUI/CNI)
        if ($user->nui_number || $user->cni_number) {
            $score += 15;
        }

        // User signature
        if ($user->signature) {
            $score += 10;
        }

        // Document has payment
        if ($proof->payment_id) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Generate document hash for verification
     */
    protected function generateDocumentHash(ProofOfLocation $proof): string
    {
        $data = implode('|', [
            $proof->user->first_name ?? '',
            $proof->user->last_name ?? '',
            $proof->address->sw_address ?? '',
            $proof->address->latitude ?? '',
            $proof->address->longitude ?? '',
            $proof->issued_at->toDateString(),
        ]);

        return strtoupper(hash('sha256', $data));
    }

    /**
     * Get company information from config
     */
    protected function getCompanyInfo(): array
    {
        return [
            'name' => config('documents.company.name', 'Ket-Up SARL'),
            'brand' => config('documents.company.brand', 'SomeWhere App'),
            'address' => config('documents.company.address', 'Ndogpassi III, Douala III, Cameroun'),
            'phone' => config('documents.company.phone'),
            'email' => config('documents.company.email'),
            'website' => config('documents.company.website', 'www.somewhere-app.com'),
            'rccm' => config('documents.company.rccm', 'CM-DLA-01-2025-B12-00208'),
            'niu' => config('documents.company.niu', 'M022517576016N'),
            'logo' => $this->getLogoDataUrl(),
        ];
    }

    /**
     * Get logo as base64 data URL
     */
    protected function getLogoDataUrl(): ?string
    {
        $logoPath = public_path(config('documents.pdf.logo_path', 'images/somewhere_icon_black.png'));

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

    /**
     * Get certified logo SVG as base64
     */
    public function getCertifiedLogoBase64(): string
    {
        return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiMyOThlNTUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBjbGFzcz0ibHVjaWRlIGx1Y2lkZS1iYWRnZS1jaGVjay1pY29uIGx1Y2lkZS1iYWRnZS1jaGVjayI+PHBhdGggZD0iTTMuODUgOC42MmE0IDQgMCAwIDEgNC43OC00Ljc3IDQgNCAwIDAgMSA2Ljc0IDAgNCA0IDAgMCAxIDQuNzggNC43OCA0IDQgMCAwIDEgMCA2Ljc0IDQgNCAwIDAgMS00Ljc3IDQuNzggNCA0IDAgMCAxLTYuNzUgMCA0IDQgMCAwIDEtNC43OC00Ljc3IDQgNCAwIDAgMSAwLTYuNzZaIi8+PHBhdGggZD0ibTkgMTIgMiAyIDQtNCIvPjwvc3ZnPg==';
    }

    /**
     * Convert signature to displayable format for PDF
     * Handles both base64 data URLs and SVG path data
     */
    public function getSignatureDataUrl(?string $signature): ?string
    {
        if (empty($signature)) {
            \Log::info('Signature is empty');
            return null;
        }

        \Log::info('Signature format check', [
            'length' => strlen($signature),
            'starts_with' => substr($signature, 0, 50),
        ]);

        // Already a data URL (base64)
        if (str_starts_with($signature, 'data:image/')) {
            \Log::info('Signature is already base64 data URL');
            return $signature;
        }

        // SVG path data (starts with M for moveto command)
        // More permissive check - if it starts with M and has L commands, it's SVG path
        if (str_starts_with(trim($signature), 'M') && str_contains($signature, 'L')) {
            \Log::info('Signature detected as SVG path data');

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 350 300" width="150" height="60">';
            $svg .= '<path d="' . htmlspecialchars(trim($signature)) . '" fill="none" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            $svg .= '</svg>';

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        \Log::info('Signature format unknown, returning as-is');
        // Unknown format, return as-is
        return $signature;
    }

    /**
     * Generate PDF with watermark for a ProofOfLocation document.
     * The watermark includes user identification and document tracking info.
     */
    public function generateWatermarkedProofPdf(ProofOfLocation $proof, ?User $downloadingUser = null): Response
    {
        // First generate the standard PDF
        $proof->load(['user', 'address.street', 'address.itineraryStreet', 'payment']);

        $data = $this->prepareProofData($proof);
        $data['documentTitle'] = $proof->isProofOfResidence() ? 'ATTESTATION DE RESIDENCE' : 'PLAN DE LOCALISATION';

        // Add watermark data to the view
        if (config('documents.watermark.enabled', true) && $downloadingUser) {
            $data['watermark'] = $this->generateWatermarkData($proof, $downloadingUser);
        }

        $viewName = $proof->isProofOfResidence() ? 'pdf.proof-of-residence' : 'pdf.location-plan';

        $pdf = Pdf::loadView($viewName, $data);
        $pdf->setPaper('A4', 'portrait');

        // Track the download
        if ($downloadingUser) {
            $this->downloadService->trackDownload(
                $proof,
                $downloadingUser,
                'download',
                config('documents.watermark.enabled', true)
            );
        }

        return $pdf->download($proof->document_number . '.pdf');
    }

    /**
     * Generate watermark data for a document.
     */
    protected function generateWatermarkData(ProofOfLocation $proof, User $user): array
    {
        $watermarkText = $this->downloadService->generateWatermarkText($proof, $user);

        return [
            'text' => $watermarkText,
            'opacity' => config('documents.watermark.opacity', 0.1),
            'angle' => config('documents.watermark.angle', -45),
            'fontSize' => config('documents.watermark.font_size', 48),
            'color' => config('documents.watermark.color', '#000000'),
        ];
    }

    /**
     * Add watermark to an existing PDF file.
     * Uses FPDI/TCPDF to overlay watermark text.
     */
    public function addWatermarkToPdf(string $pdfPath, string $watermarkText, array $options = []): string
    {
        $opacity = $options['opacity'] ?? 0.1;
        $angle = $options['angle'] ?? -45;
        $fontSize = $options['fontSize'] ?? 48;
        $color = $options['color'] ?? '#000000';

        try {
            // Create new PDF with FPDI
            $pdf = new Fpdi();

            // Get page count from source PDF
            $pageCount = $pdf->setSourceFile($pdfPath);

            // Process each page
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // Import the page
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                // Add page with same orientation
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

                // Use the imported page
                $pdf->useTemplate($templateId);

                // Add watermark
                $this->applyWatermarkToPage($pdf, $watermarkText, $size, [
                    'opacity' => $opacity,
                    'angle' => $angle,
                    'fontSize' => $fontSize,
                    'color' => $color,
                ]);
            }

            // Generate output path
            $outputPath = str_replace('.pdf', '_watermarked.pdf', $pdfPath);
            $pdf->Output($outputPath, 'F');

            return $outputPath;

        } catch (\Exception $e) {
            Log::error('Failed to add watermark to PDF', [
                'path' => $pdfPath,
                'error' => $e->getMessage(),
            ]);

            // Return original path if watermarking fails
            return $pdfPath;
        }
    }

    /**
     * Apply watermark text to a single PDF page.
     */
    protected function applyWatermarkToPage(Fpdi $pdf, string $watermarkText, array $size, array $options): void
    {
        $opacity = $options['opacity'];
        $angle = $options['angle'];
        $fontSize = $options['fontSize'];
        $color = $this->hexToRgb($options['color']);

        // Set transparency
        $pdf->SetAlpha($opacity);

        // Set font
        $pdf->SetFont('helvetica', '', $fontSize);

        // Set text color
        $pdf->SetTextColor($color['r'], $color['g'], $color['b']);

        // Calculate center position
        $centerX = $size['width'] / 2;
        $centerY = $size['height'] / 2;

        // Start transformation
        $pdf->StartTransform();

        // Rotate around center
        $pdf->Rotate($angle, $centerX, $centerY);

        // Get text width to center it
        $textWidth = $pdf->GetStringWidth($watermarkText);
        $x = $centerX - ($textWidth / 2);
        $y = $centerY;

        // Draw the watermark text
        $pdf->Text($x, $y, $watermarkText);

        // Stop transformation
        $pdf->StopTransform();

        // Reset transparency
        $pdf->SetAlpha(1);
    }

    /**
     * Convert hex color to RGB array.
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Generate watermarked PDF response with download tracking.
     */
    public function downloadWithWatermark(
        ProofOfLocation $proof,
        ?User $user = null,
        string $downloadType = 'download'
    ): Response {
        // Generate standard PDF first
        $pdf = $this->generateProofPdf($proof);

        // If watermarking is enabled and we have a user
        if (config('documents.watermark.enabled', true) && $user) {
            // Track the download
            $this->downloadService->trackDownload($proof, $user, $downloadType, true);
        }

        return $pdf;
    }

    /**
     * Stream PDF for viewing (with optional watermark).
     */
    public function streamProofPdf(ProofOfLocation $proof, ?User $viewer = null): Response
    {
        $proof->load(['user', 'address.street', 'address.itineraryStreet', 'payment']);

        $data = $this->prepareProofData($proof);
        $data['documentTitle'] = $proof->isProofOfResidence() ? 'ATTESTATION DE RESIDENCE' : 'PLAN DE LOCALISATION';

        // Add subtle watermark for viewing
        if (config('documents.watermark.enabled', true) && $viewer) {
            $data['watermark'] = [
                'text' => 'VISUALISATION - ' . now()->format('d/m/Y H:i'),
                'opacity' => 0.05,
                'angle' => -45,
                'fontSize' => 36,
                'color' => '#666666',
            ];

            // Track as view
            $this->downloadService->trackDownload($proof, $viewer, 'view', false);
        }

        $viewName = $proof->isProofOfResidence() ? 'pdf.proof-of-residence' : 'pdf.location-plan';

        $pdf = Pdf::loadView($viewName, $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream($proof->document_number . '.pdf');
    }

    /**
     * Get download statistics for a document.
     */
    public function getDocumentDownloadStats(ProofOfLocation $proof): array
    {
        return $this->downloadService->getDownloadStats($proof);
    }
}
