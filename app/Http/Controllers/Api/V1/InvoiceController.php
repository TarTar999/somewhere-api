<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\QrCodeService;
use App\Services\WebAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected QrCodeService $qrCodeService,
        protected WebAccessService $webAccessService
    ) {}

    /**
     * List user's invoices
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $invoices = $this->invoiceService->getUserInvoices($user, $request->get('perPage', 15));

        $data = $invoices->map(fn($i) => $this->invoiceService->formatInvoiceForResponse($i));

        return $this->paginated($invoices->setCollection($data), 'Invoices retrieved');
    }

    /**
     * Get single invoice details
     */
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with(['payment'])->find($id);

        if (!$invoice) {
            return $this->error('Invoice not found', 404);
        }

        if ($invoice->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success(
            $this->invoiceService->formatInvoiceForResponse($invoice),
            'Invoice retrieved'
        );
    }

    /**
     * Download invoice PDF
     */
    public function download(int $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        if ($invoice->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $this->invoiceService->download($invoice);
    }

    /**
     * Generate QR code for web access
     */
    public function generateWebAccessQr(int $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return $this->error('Invoice not found', 404);
        }

        if ($invoice->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // Create web access token
        $token = $this->webAccessService->createInvoiceAccessToken(
            auth()->user(),
            $invoice,
            60
        );

        // Generate QR code
        $qrCode = $this->qrCodeService->generatePngForWebAccess($token, 300);
        $qrData = $this->webAccessService->generateQrCodeData($token);

        return $this->success([
            'qrCode' => $qrCode,
            'qrData' => $qrData,
            'invoice' => $this->invoiceService->formatInvoiceForResponse($invoice),
        ], 'QR code generated');
    }
}
