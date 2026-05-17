<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\Payment;
use App\Models\ProofOfLocation;
use App\Services\FapshiService;
use App\Services\InvoiceService;
use App\Services\ProofOfLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        protected FapshiService $fapshiService,
        protected ProofOfLocationService $proofService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Get payment configuration
     */
    public function getConfig(): JsonResponse
    {
        return $this->success([
            'prices' => [
                'location_plan' => (int) config('documents.prices.location_plan', 2000),
                'proof_of_residence' => (int) config('documents.prices.proof_of_residence', 3000),
            ],
            'proofOfLocationPrice' => (int) config('documents.prices.location_plan', 2000), // Backward compatibility
            'currency' => 'XAF',
            'paymentMethods' => ['mobile_money', 'orange_money'],
            'isSandbox' => str_starts_with(config('services.fapshi.api_key', ''), 'FAK_TEST_'),
            'validityMonths' => (int) config('documents.validity_months', 3),
        ], 'Payment configuration');
    }

    /**
     * Initiate payment for document (hosted checkout)
     * Supports both location_plan and proof_of_residence
     */
    public function initiateDocumentPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|exists:addresses,id',
            'documentType' => 'required|in:location_plan,proof_of_residence',
            'redirectUrl' => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $address = Address::find($request->addressId);
        $user = auth()->user();
        $documentType = $request->documentType;

        // Verify ownership or domiciliation for proof_of_residence
        $hasAccess = $address->user_id === $user->id;

        if ($documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE && !$hasAccess) {
            // Check if user has approved domiciliation
            $hasAccess = $address->domiciliations()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->exists();
        }

        if (!$hasAccess) {
            return $this->error('Unauthorized', 403);
        }

        // Verify address is approved (only for proof_of_residence)
        if ($documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE && $address->verification_status !== 'approved') {
            return $this->error('Address must be verified before purchasing proof of residence', 400);
        }

        // Check if user already has active document for this address of this type
        $existingProof = $user->proofOfLocations()
            ->where('address_id', $address->id)
            ->where('document_type', $documentType)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingProof) {
            $typeLabel = $existingProof->document_type_label;
            return $this->error("You already have an active {$typeLabel} for this address", 400, [
                'existingDocument' => $this->proofService->formatProofForResponse($existingProof),
            ]);
        }

        // Check for pending payment of same type
        $pendingPayment = Payment::where('user_id', $user->id)
            ->where('address_id', $address->id)
            ->where('type', $documentType)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingPayment) {
            return $this->success([
                'paymentId' => $pendingPayment->id,
                'transactionId' => $pendingPayment->transaction_id,
                'paymentLink' => $pendingPayment->payment_link,
                'amount' => $pendingPayment->amount,
                'currency' => $pendingPayment->currency,
                'documentType' => $documentType,
                'status' => 'pending',
                'expiresAt' => $pendingPayment->expires_at?->toISOString(),
            ], 'Existing pending payment found');
        }

        // Create new payment
        $payment = $this->fapshiService->createDocumentPayment(
            $user,
            $address,
            $documentType,
            $request->redirectUrl
        );

        if ($payment->isFailed()) {
            return $this->error('Failed to create payment: ' . $payment->failure_reason, 500);
        }

        return $this->success([
            'paymentId' => $payment->id,
            'transactionId' => $payment->transaction_id,
            'paymentLink' => $payment->payment_link,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'documentType' => $documentType,
            'status' => $payment->status,
            'expiresAt' => $payment->expires_at?->toISOString(),
        ], 'Payment initiated');
    }

    /**
     * Initiate payment for proof of location (hosted checkout)
     * @deprecated Use initiateDocumentPayment instead
     */
    public function initiateProofOfLocationPayment(Request $request): JsonResponse
    {
        // Add document type and forward to new method
        $request->merge(['documentType' => ProofOfLocation::TYPE_LOCATION_PLAN]);
        return $this->initiateDocumentPayment($request);
    }

    /**
     * Initiate direct payment (Mobile Money)
     */
    public function initiateDirectPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|exists:addresses,id',
            'documentType' => 'nullable|in:location_plan,proof_of_residence',
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $address = Address::find($request->addressId);
        $user = auth()->user();
        $documentType = $request->documentType ?? ProofOfLocation::TYPE_LOCATION_PLAN;

        // Verify ownership or domiciliation for proof_of_residence
        $hasAccess = $address->user_id === $user->id;

        if ($documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE && !$hasAccess) {
            $hasAccess = $address->domiciliations()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->exists();
        }

        if (!$hasAccess) {
            return $this->error('Unauthorized', 403);
        }

        // Verify address is approved (only for proof_of_residence)
        if ($documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE && $address->verification_status !== 'approved') {
            return $this->error('Address must be verified before purchasing proof of residence', 400);
        }

        // Create direct payment
        $payment = $this->fapshiService->createDirectDocumentPayment(
            $user,
            $address,
            $documentType,
            $request->phone
        );

        if ($payment->isFailed()) {
            return $this->error('Failed to create payment: ' . $payment->failure_reason, 500);
        }

        return $this->success([
            'paymentId' => $payment->id,
            'transactionId' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'documentType' => $documentType,
            'status' => $payment->status,
            'message' => 'Please confirm the payment on your phone',
        ], 'Direct payment initiated');
    }

    /**
     * Get payment status
     */
    public function getStatus(int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return $this->error('Payment not found', 404);
        }

        if ($payment->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        // If pending, check with Fapshi for latest status
        if ($payment->isPending() && $payment->transaction_id) {
            $status = $this->fapshiService->getPaymentStatus($payment->transaction_id);

            if (isset($status['status'])) {
                $fapshiStatus = strtoupper($status['status']);

                if ($fapshiStatus === 'SUCCESSFUL' && !$payment->isSuccessful()) {
                    $payment->markAsSuccessful($status);
                    $this->processSuccessfulPayment($payment);
                } elseif ($fapshiStatus === 'FAILED') {
                    $payment->markAsFailed($status['reason'] ?? 'Payment failed', $status);
                } elseif ($fapshiStatus === 'EXPIRED') {
                    $payment->markAsExpired();
                }

                $payment->refresh();
            }
        }

        $response = [
            'paymentId' => $payment->id,
            'transactionId' => $payment->transaction_id,
            'externalId' => $payment->external_id,
            'type' => $payment->type,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'paymentLink' => $payment->payment_link,
            'paidAt' => $payment->paid_at?->toISOString(),
            'failureReason' => $payment->failure_reason,
            'createdAt' => $payment->created_at->toISOString(),
        ];

        // If successful and document type, include document data
        if ($payment->isSuccessful() && in_array($payment->type, [ProofOfLocation::TYPE_LOCATION_PLAN, ProofOfLocation::TYPE_PROOF_OF_RESIDENCE, 'proof_of_location'])) {
            $proof = $payment->proofOfLocation;
            if ($proof) {
                $response['document'] = $this->proofService->formatProofForResponse($proof);
                $response['proofOfLocation'] = $response['document']; // Backward compatibility
            }
        }

        return $this->success($response, 'Payment status retrieved');
    }

    /**
     * List user payments
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $payments = $user->payments()
            ->with(['address', 'proofOfLocation'])
            ->orderByDesc('created_at')
            ->paginate($request->get('perPage', 15));

        $data = $payments->map(fn($p) => [
            'id' => $p->id,
            'transactionId' => $p->transaction_id,
            'type' => $p->type,
            'amount' => $p->amount,
            'currency' => $p->currency,
            'status' => $p->status,
            'address' => $p->address ? [
                'id' => $p->address->id,
                'swAddress' => $p->address->sw_address,
                'displayName' => $p->address->display_name,
            ] : null,
            'paidAt' => $p->paid_at?->toISOString(),
            'createdAt' => $p->created_at->toISOString(),
        ]);

        return $this->paginated($payments->setCollection($data), 'Payments retrieved');
    }

    /**
     * Handle Fapshi webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        Log::info('Fapshi webhook received', $request->all());

        $payment = $this->fapshiService->processWebhook($request->all());

        if (!$payment) {
            return response()->json(['message' => 'Webhook processed'], 200);
        }

        // If payment is now successful, generate document
        if ($payment->isSuccessful() && in_array($payment->type, [ProofOfLocation::TYPE_LOCATION_PLAN, ProofOfLocation::TYPE_PROOF_OF_RESIDENCE, 'proof_of_location'])) {
            $this->processSuccessfulPayment($payment);
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }

    /**
     * Process successful payment (generate document, invoice, receipt, etc.)
     */
    protected function processSuccessfulPayment(Payment $payment): void
    {
        try {
            // Generate document based on payment type
            $documentTypes = [
                ProofOfLocation::TYPE_LOCATION_PLAN,
                ProofOfLocation::TYPE_PROOF_OF_RESIDENCE,
                'proof_of_location', // Legacy type
            ];

            if (in_array($payment->type, $documentTypes) && !$payment->proofOfLocation) {
                // Map legacy type to new type
                $documentType = $payment->type === 'proof_of_location'
                    ? ProofOfLocation::TYPE_LOCATION_PLAN
                    : $payment->type;

                $this->proofService->generateAfterPayment($payment, $documentType);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process successful payment', [
                'payment_id' => $payment->id,
                'type' => $payment->type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
