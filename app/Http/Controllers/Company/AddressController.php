<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\CompanyDocument;
use App\Models\ProofOfLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;
        $memberIds = $company->members()->pluck('users.id');

        $query = Address::whereIn('user_id', $memberIds)
            ->with(['user:id,first_name,last_name,email', 'street:id,display_name,commune_name']);

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sw_address', 'like', "%{$search}%")
                  ->orWhere('lieu_dit', 'like', "%{$search}%")
                  ->orWhere('quarter', 'like', "%{$search}%");
            });
        }

        $addresses = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn ($address) => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'streetName' => $address->street?->display_name,
                'lieuDit' => $address->lieu_dit,
                'quarter' => $address->quarter,
                'commune' => $address->street?->commune_name,
                'verificationStatus' => $address->verification_status,
                'createdAt' => $address->created_at->diffForHumans(),
                'owner' => [
                    'id' => $address->user->id,
                    'name' => $address->user->full_name,
                ],
            ]);

        return Inertia::render('company/addresses/index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
            ],
            'userRole' => $user->getCompanyRole($company),
            'addresses' => $addresses,
            'filters' => [
                'search' => $request->get('search'),
            ],
        ]);
    }

    public function show(Address $address): Response
    {
        $user = auth()->user();
        $company = $user->currentCompany;
        $memberIds = $company->members()->pluck('users.id');

        // Verify access
        if (!$memberIds->contains($address->user_id)) {
            abort(404);
        }

        $address->load(['user:id,first_name,last_name,email', 'street']);

        // Get existing documents for this address created by company
        $documents = ProofOfLocation::where('address_id', $address->id)
            ->where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'documentNumber' => $doc->document_number,
                'documentType' => $doc->document_type,
                'documentTypeLabel' => $doc->document_type_label,
                'status' => $doc->status,
                'isExpired' => $doc->isExpired(),
                'expiresAt' => $doc->expires_at?->toIso8601String(),
                'createdAt' => $doc->created_at->diffForHumans(),
            ]);

        return Inertia::render('company/addresses/show', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
            ],
            'userRole' => $user->getCompanyRole($company),
            'address' => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'latitude' => (float) $address->latitude,
                'longitude' => (float) $address->longitude,
                'streetNumber' => $address->street_number,
                'streetName' => $address->street?->display_name,
                'lieuDit' => $address->lieu_dit,
                'quarter' => $address->quarter,
                'subQuarter' => $address->sub_quarter,
                'commune' => $address->street?->commune_name,
                'houseType' => $address->house_type,
                'homeStatus' => $address->home_status,
                'description' => $address->description,
                'verificationStatus' => $address->verification_status,
                'isNonHabitation' => $address->is_non_habitation,
                'nonHabitationType' => $address->non_habitation_type,
                'owner' => [
                    'id' => $address->user->id,
                    'name' => $address->user->full_name,
                    'email' => $address->user->email,
                ],
            ],
            'documents' => $documents,
            'canCreateDocument' => $company->canCreateDocument(),
            'remainingDocuments' => $company->getRemainingDocuments(),
        ]);
    }

    public function createDocument(Request $request, Address $address): RedirectResponse
    {
        $request->validate([
            'documentType' => 'required|in:location_plan,proof_of_residence',
        ]);

        $user = auth()->user();
        $company = $user->currentCompany;
        $memberIds = $company->members()->pluck('users.id');

        // Verify access
        if (!$memberIds->contains($address->user_id)) {
            return back()->withErrors(['address' => 'Adresse non accessible']);
        }

        if (!$company->canCreateDocument()) {
            return back()->withErrors(['document' => 'Limite mensuelle de documents atteinte']);
        }

        // For proof of residence, address must be verified
        if ($request->documentType === ProofOfLocation::TYPE_PROOF_OF_RESIDENCE) {
            if ($address->verification_status !== 'approved') {
                return back()->withErrors(['document' => 'L\'adresse doit être vérifiée pour créer une attestation de résidence']);
            }
        }

        // Check for existing active document
        $existingDoc = ProofOfLocation::where('address_id', $address->id)
            ->where('document_type', $request->documentType)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingDoc) {
            return back()->withErrors(['document' => 'Un document actif de ce type existe déjà pour cette adresse']);
        }

        try {
            $subscription = $company->activeSubscription;

            $proof = ProofOfLocation::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'company_id' => $company->id,
                'is_company_document' => true,
                'document_type' => $request->documentType,
                'document_number' => ProofOfLocation::generateDocumentNumber($user, $address, $request->documentType),
                'status' => 'active',
                'issued_at' => now(),
                'expires_at' => now()->addMonths(config('documents.validity_months', 3)),
                'price' => 0,
            ]);

            // Track document for monthly limit
            CompanyDocument::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'proof_of_location_id' => $proof->id,
                'document_type' => $request->documentType,
                'period_start' => $subscription->current_period_start,
                'period_end' => $subscription->current_period_end,
            ]);

            return back()->with('success', 'Document créé avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['document' => 'Erreur lors de la création du document']);
        }
    }
}
