<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\Payment;
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
            'proofOfLocationPrice' => (int) config('services.fapshi.proof_of_location_price', 1000),
            'currency' => 'XAF',
            'paymentMethods' => ['mobile_money', 'orange_money'],
            'isSandbox' => str_starts_with(config('services.fapshi.api_key', ''), 'FAK_TEST_'),
        ], 'Payment configuration');
    }

    /**
     * Initiate payment for proof of location (hosted checkout)
     */
    public function initiateProofOfLocationPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|exists:addresses,id',
            'redirectUrl' => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $address = Address::find($request->addressId);
        $user = auth()->user();

        // Verify ownership
        if ($address->user_id !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        // Verify address is approved
        if ($address->verification_status !== 'approved') {
            return $this->error('Address must be verified before purchasing proof of location', 400);
        }

        // Check if user already has active proof for this address
        $existingProof = $user->proofOfLocations()
            ->where('address_id', $address->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingProof) {
            return $this->error('You already have an active proof of location for this address', 400, [
                'existingProof' => $this->proofService->formatProofForResponse($existingProof),
            ]);
        }

        // Check for pending payment
        $pendingPayment = Payment::where('user_id', $user->id)
            ->where('address_id', $address->id)
            ->where('type', 'proof_of_location')
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
                'status' => 'pending',
                'expiresAt' => $pendingPayment->expires_at?->toISOString(),
            ], 'Existing pending payment found');
        }

        // Create new payment
        $payment = $this->fapshiService->createProofOfLocationPayment(
            $user,
            $address,
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
            'status' => $payment->status,
            'expiresAt' => $payment->expires_at?->toISOString(),
        ], 'Payment initiated');
    }

    /**
     * Initiate direct payment (Mobile Money)
     */
    public function initiateDirectPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|exists:addresses,id',
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $address = Address::find($request->addressId);
        $user = auth()->user();

        // Verify ownership
        if ($address->user_id !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        // Verify address is approved
        if ($address->verification_status !== 'approved') {
            return $this->error('Address must be verified before purchasing proof of location', 400);
        }

        // Create direct payment
        $payment = $this->fapshiService->createDirectProofOfLocationPayment(
            $user,
            $address,
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

        // If successful and proof of location type, include proof data
        if ($payment->isSuccessful() && $payment->type === 'proof_of_location') {
            $proof = $payment->proofOfLocation;
            if ($proof) {
                $response['proofOfLocation'] = $this->proofService->formatProofForResponse($proof);
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

        // If payment is now successful, generate proof of location
        if ($payment->isSuccessful() && $payment->type === 'proof_of_location') {
            $this->processSuccessfulPayment($payment);
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }

    /**
     * Process successful payment (generate proof, invoice, etc.)
     */
    protected function processSuccessfulPayment(Payment $payment): void
    {
        try {
            // Generate proof of location
            if ($payment->type === 'proof_of_location' && !$payment->proofOfLocation) {
                $this->proofService->generateAfterPayment($payment);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process successful payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
