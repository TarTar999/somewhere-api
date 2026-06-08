<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\ProofOfLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's addresses
        $addresses = Address::where('user_id', $user->id)
            ->with('street')
            ->latest()
            ->get()
            ->map(fn($address) => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'displayName' => $address->display_name,
                'quarter' => $address->quarter,
                'subQuarter' => $address->sub_quarter,
                'houseType' => $address->house_type,
                'verificationStatus' => $address->verification_status,
                'latitude' => (float) $address->latitude,
                'longitude' => (float) $address->longitude,
                'street' => $address->street ? [
                    'code' => $address->street->code,
                    'displayName' => $address->street->display_name,
                ] : null,
                'createdAt' => $address->created_at->toIso8601String(),
            ]);

        // Get user's documents
        $documents = ProofOfLocation::where('user_id', $user->id)
            ->with('address')
            ->latest()
            ->get()
            ->map(fn($doc) => [
                'id' => $doc->id,
                'documentType' => $doc->document_type,
                'documentTypeLabel' => $doc->document_type_label,
                'documentNumber' => $doc->document_number,
                'verificationCode' => $doc->verification_code,
                'status' => $doc->status,
                'isActive' => $doc->isActive(),
                'isExpired' => $doc->isExpired(),
                'issuedAt' => $doc->issued_at?->toIso8601String(),
                'expiresAt' => $doc->expires_at?->toIso8601String(),
                'downloadCount' => $doc->download_count,
                'address' => $doc->address ? [
                    'id' => $doc->address->id,
                    'swAddress' => $doc->address->sw_address,
                    'displayName' => $doc->address->display_name,
                ] : null,
            ]);

        // Stats
        $stats = [
            'totalAddresses' => $addresses->count(),
            'verifiedAddresses' => $addresses->where('verificationStatus', 'approved')->count(),
            'pendingAddresses' => $addresses->where('verificationStatus', 'pending')->count(),
            'totalDocuments' => $documents->count(),
            'activeDocuments' => $documents->where('isActive', true)->count(),
            'expiredDocuments' => $documents->where('isExpired', true)->count(),
        ];

        return Inertia::render('dashboard', [
            'addresses' => $addresses,
            'documents' => $documents,
            'stats' => $stats,
        ]);
    }
}
