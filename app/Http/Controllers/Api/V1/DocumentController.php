<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\Invoice;
use App\Models\ProofOfLocation;
use App\Models\Receipt;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * List all documents for authenticated user
     * Can filter by type, address, and status
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:location_plan,proof_of_residence,invoice,receipt,all',
            'address_id' => 'nullable|exists:addresses,id',
            'status' => 'nullable|in:active,expired,revoked,all',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $type = $request->type ?? 'all';
        $status = $request->status ?? 'all';
        $limit = $request->limit ?? 50;
        $addressId = $request->address_id;

        $documents = [];

        // Location plans and proofs of residence
        if (in_array($type, ['location_plan', 'proof_of_residence', 'all'])) {
            $proofQuery = ProofOfLocation::with(['address.street'])
                ->where('user_id', $user->id);

            if ($type !== 'all') {
                $proofQuery->where('document_type', $type);
            }

            if ($addressId) {
                $proofQuery->where('address_id', $addressId);
            }

            if ($status !== 'all') {
                $proofQuery->where('status', $status);
            }

            $proofs = $proofQuery->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($proofs as $proof) {
                $documents[] = $this->formatProofDocument($proof);
            }
        }

        // Invoices
        if (in_array($type, ['invoice', 'all'])) {
            $invoices = Invoice::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($invoices as $invoice) {
                $documents[] = $this->formatInvoiceDocument($invoice);
            }
        }

        // Receipts
        if (in_array($type, ['receipt', 'all'])) {
            $receipts = Receipt::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($receipts as $receipt) {
                $documents[] = $this->formatReceiptDocument($receipt);
            }
        }

        // Sort all documents by date
        usort($documents, fn($a, $b) => strtotime($b['createdAt']) - strtotime($a['createdAt']));

        // Apply limit to combined results
        $documents = array_slice($documents, 0, $limit);

        return $this->success([
            'documents' => $documents,
            'count' => count($documents),
            'filters' => [
                'type' => $type,
                'addressId' => $addressId,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Get documents for a specific address
     */
    public function byAddress(int $addressId, Request $request): JsonResponse
    {
        $user = auth()->user();

        // Check if user has access to this address
        $address = Address::where('id', $addressId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('domiciliations', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id)->where('status', 'approved');
                  });
            })
            ->first();

        if (!$address) {
            return $this->error('Address not found or access denied', 404);
        }

        $type = $request->type ?? 'all';

        $proofQuery = ProofOfLocation::with(['user'])
            ->where('address_id', $addressId);

        if ($type !== 'all') {
            $proofQuery->where('document_type', $type);
        }

        $proofs = $proofQuery->orderBy('created_at', 'desc')->get();

        $documents = $proofs->map(fn($p) => $this->formatProofDocument($p))->toArray();

        return $this->success([
            'addressId' => $addressId,
            'address' => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'displayName' => $address->display_name,
            ],
            'documents' => $documents,
            'count' => count($documents),
        ]);
    }

    /**
     * Verify a document by verification code
     */
    public function verify(string $code): JsonResponse
    {
        // Try to find in proofs
        $proof = ProofOfLocation::where('verification_code', $code)->first();
        if ($proof) {
            return $this->success([
                'valid' => true,
                'documentType' => $proof->document_type,
                'documentTypeLabel' => $proof->document_type_label,
                'documentNumber' => $proof->document_number,
                'issuedAt' => $proof->issued_at->toIso8601String(),
                'expiresAt' => $proof->expires_at->toIso8601String(),
                'status' => $proof->status,
                'isActive' => $proof->isActive(),
                'isExpired' => $proof->isExpired(),
            ]);
        }

        // Try to find in invoices
        $invoice = Invoice::where('verification_code', $code)->first();
        if ($invoice) {
            return $this->success([
                'valid' => true,
                'documentType' => 'invoice',
                'documentTypeLabel' => 'Facture',
                'documentNumber' => $invoice->invoice_number,
                'issuedAt' => $invoice->created_at->toIso8601String(),
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
            ]);
        }

        // Try to find in receipts
        $receipt = Receipt::where('verification_code', $code)->first();
        if ($receipt) {
            return $this->success([
                'valid' => true,
                'documentType' => 'receipt',
                'documentTypeLabel' => 'Recu',
                'documentNumber' => $receipt->receipt_number,
                'issuedAt' => $receipt->paid_at->toIso8601String(),
                'amount' => $receipt->amount,
                'currency' => $receipt->currency,
            ]);
        }

        return $this->error('Document not found', 404, [
            'valid' => false,
            'message' => 'Aucun document ne correspond a ce code de verification',
        ]);
    }

    /**
     * Get document prices
     */
    public function prices(): JsonResponse
    {
        return $this->success([
            'prices' => [
                'location_plan' => [
                    'amount' => config('documents.prices.location_plan', 2000),
                    'currency' => 'XAF',
                    'label' => 'Plan de Localisation',
                    'description' => 'Document officiel indiquant la localisation precise de votre adresse',
                ],
                'proof_of_residence' => [
                    'amount' => config('documents.prices.proof_of_residence', 3000),
                    'currency' => 'XAF',
                    'label' => 'Attestation de Residence',
                    'description' => 'Document officiel attestant de votre residence a cette adresse',
                ],
            ],
            'validityMonths' => config('documents.validity_months', 3),
        ]);
    }

    /**
     * Download document as PDF
     * Route: GET /api/documents/{type}/{id}/download
     */
    public function download(string $type, int $id): Response|JsonResponse
    {
        $user = auth()->user();

        switch ($type) {
            case 'location_plan':
            case 'proof_of_residence':
                $proof = ProofOfLocation::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$proof) {
                    return $this->error('Document not found', 404);
                }

                $proof->recordDownload();
                return $this->pdfService->generateProofPdf($proof);

            case 'invoice':
                $invoice = Invoice::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$invoice) {
                    return $this->error('Document not found', 404);
                }

                return $this->pdfService->generateInvoicePdf($invoice);

            case 'receipt':
                $receipt = Receipt::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$receipt) {
                    return $this->error('Document not found', 404);
                }

                return $this->pdfService->generateReceiptPdf($receipt);

            default:
                return $this->error('Invalid document type', 400);
        }
    }

    /**
     * Format proof document for response
     */
    protected function formatProofDocument(ProofOfLocation $proof): array
    {
        return [
            'id' => $proof->id,
            'type' => $proof->document_type,
            'typeLabel' => $proof->document_type_label,
            'documentNumber' => $proof->document_number,
            'verificationCode' => $proof->verification_code,
            'verificationUrl' => $proof->getVerificationUrl(),
            'status' => $proof->status,
            'isActive' => $proof->isActive(),
            'isExpired' => $proof->isExpired(),
            'price' => $proof->price,
            'address' => $proof->address ? [
                'id' => $proof->address->id,
                'swAddress' => $proof->address->sw_address,
                'displayName' => $proof->address->display_name,
            ] : null,
            'issuedAt' => $proof->issued_at->toIso8601String(),
            'expiresAt' => $proof->expires_at->toIso8601String(),
            'downloadUrl' => "/api/documents/{$proof->document_type}/{$proof->id}/download",
            'createdAt' => $proof->created_at->toIso8601String(),
        ];
    }

    /**
     * Format invoice document for response
     */
    protected function formatInvoiceDocument(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'type' => 'invoice',
            'typeLabel' => 'Facture',
            'documentNumber' => $invoice->invoice_number,
            'verificationCode' => $invoice->verification_code,
            'verificationUrl' => $invoice->verification_code
                ? config('app.url') . '/verify/' . $invoice->verification_code
                : null,
            'description' => $invoice->description,
            'amount' => $invoice->amount,
            'taxAmount' => $invoice->tax_amount,
            'totalAmount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'invoiceDate' => $invoice->invoice_date->toIso8601String(),
            'paidAt' => $invoice->paid_at?->toIso8601String(),
            'downloadUrl' => "/api/documents/invoice/{$invoice->id}/download",
            'createdAt' => $invoice->created_at->toIso8601String(),
        ];
    }

    /**
     * Format receipt document for response
     */
    protected function formatReceiptDocument(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'type' => 'receipt',
            'typeLabel' => 'Recu',
            'documentNumber' => $receipt->receipt_number,
            'verificationCode' => $receipt->verification_code,
            'verificationUrl' => $receipt->getVerificationUrl(),
            'description' => $receipt->description,
            'amount' => $receipt->amount,
            'currency' => $receipt->currency,
            'paymentMethod' => $receipt->payment_method,
            'paidAt' => $receipt->paid_at->toIso8601String(),
            'downloadUrl' => "/api/documents/receipt/{$receipt->id}/download",
            'createdAt' => $receipt->created_at->toIso8601String(),
        ];
    }
}
