<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\CompanyDocument;
use App\Models\ProofOfLocation;
use App\Services\CompanySubscriptionService;
use App\Services\ProofOfLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyDocumentController extends Controller
{
    public function __construct(
        protected ProofOfLocationService $proofService,
        protected CompanySubscriptionService $subscriptionService
    ) {}

    public function create(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->hasActiveSubscription()) {
            return $this->error('No active subscription', 403);
        }

        if (!$company->canCreateDocument()) {
            return $this->error('Monthly document limit reached', 403);
        }

        $request->validate([
            'addressId' => 'required|exists:addresses,id',
            'documentType' => 'required|in:location_plan,proof_of_residence',
        ]);

        $address = Address::findOrFail($request->addressId);

        // Check if user can access this address (belongs to a company member)
        $memberIds = $company->members()->pluck('users.id');
        if (!$memberIds->contains($address->user_id)) {
            return $this->error('Address not accessible', 403);
        }

        // For proof of residence, address must be verified
        if ($request->documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE) {
            if ($address->verification_status !== 'approved') {
                return $this->error('Address must be verified for proof of residence', 400);
            }
        }

        // Check for existing active document of same type
        $existingDoc = ProofOfLocation::where('address_id', $address->id)
            ->where('document_type', $request->documentType)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingDoc) {
            return $this->error('An active document of this type already exists for this address', 400);
        }

        try {
            $proof = $this->createCompanyDocument($user, $address, $company, $request->documentType);

            return $this->success([
                'document' => $this->formatDocument($proof),
                'remainingDocuments' => $company->getRemainingDocuments(),
            ], 'Document created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function usage(): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        return $this->success($this->subscriptionService->getUsageStats($company));
    }

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        $documents = ProofOfLocation::where('company_id', $company->id)
            ->with(['user:id,first_name,last_name', 'address:id,sw_address'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $formattedDocs = collect($documents->items())->map(fn ($doc) => $this->formatDocument($doc));

        return $this->paginated(
            $documents->setCollection(collect($formattedDocs)),
            'Company documents retrieved'
        );
    }

    protected function createCompanyDocument($user, $address, $company, $documentType): ProofOfLocation
    {
        $subscription = $company->activeSubscription;

        // Create the proof of location
        $proof = ProofOfLocation::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'company_id' => $company->id,
            'is_company_document' => true,
            'document_type' => $documentType,
            'document_number' => ProofOfLocation::generateDocumentNumber($user, $address, $documentType),
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => now()->addMonths(config('documents.validity_months', 3)),
            'price' => 0, // Free for company members
        ]);

        // Track document for monthly limit
        CompanyDocument::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'proof_of_location_id' => $proof->id,
            'document_type' => $documentType,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
        ]);

        // Generate PDF (reuse existing service if available)
        // $this->proofService->generatePdf($proof);

        return $proof;
    }

    protected function formatDocument(ProofOfLocation $doc): array
    {
        return [
            'id' => $doc->id,
            'documentNumber' => $doc->document_number,
            'documentType' => $doc->document_type,
            'documentTypeLabel' => $doc->document_type_label,
            'status' => $doc->status,
            'verificationCode' => $doc->verification_code,
            'issuedAt' => $doc->issued_at?->toIso8601String(),
            'expiresAt' => $doc->expires_at?->toIso8601String(),
            'isExpired' => $doc->isExpired(),
            'downloadCount' => $doc->download_count,
            'address' => $doc->address ? [
                'id' => $doc->address->id,
                'swAddress' => $doc->address->sw_address,
            ] : null,
            'createdBy' => $doc->user ? [
                'id' => $doc->user->id,
                'name' => $doc->user->full_name,
            ] : null,
        ];
    }
}
