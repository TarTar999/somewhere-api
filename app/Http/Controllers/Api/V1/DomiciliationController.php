<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Models\Domiciliation;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DomiciliationController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * List all domiciliations for the authenticated user
     */
    public function index(): JsonResponse
    {
        $domiciliations = Domiciliation::with(['address.street', 'invitedBy'])
            ->forUser(auth()->id())
            ->approved()
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($this->formatDomiciliations($domiciliations));
    }

    /**
     * Get details of a specific domiciliation
     */
    public function show(Domiciliation $domiciliation): JsonResponse
    {
        if ($domiciliation->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $domiciliation->load(['address.street', 'invitedBy']);

        return $this->success($this->formatDomiciliation($domiciliation));
    }

    /**
     * Create a new domiciliation (self-domiciliate to an address)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|exists:addresses,id',
            'name' => 'nullable|string|max:100',
            'role' => 'nullable|in:owner,resident,visitor',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $addressId = $request->address_id;

        // Check if already domiciliated at this address
        $existing = Domiciliation::where('user_id', $user->id)
            ->where('address_id', $addressId)
            ->first();

        if ($existing) {
            return $this->error('You are already domiciliated at this address', 409);
        }

        // Check if address belongs to user (then they can self-domiciliate)
        $address = Address::find($addressId);
        if ($address->user_id !== $user->id) {
            return $this->error('You can only self-domiciliate to your own addresses. Use invitation for others.', 403);
        }

        DB::transaction(function () use ($user, $addressId, $request, &$domiciliation) {
            // If setting as primary, unset other primaries
            if ($request->is_primary) {
                Domiciliation::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $domiciliation = Domiciliation::create([
                'user_id' => $user->id,
                'address_id' => $addressId,
                'invited_by' => null, // Self-domiciliation
                'name' => $request->name ?? 'Domicile',
                'role' => $request->role ?? 'owner',
                'status' => 'approved',
                'is_primary' => $request->is_primary ?? false,
            ]);
        });

        $domiciliation->load(['address.street']);

        return $this->success(
            $this->formatDomiciliation($domiciliation),
            'Domiciliation created successfully',
            201
        );
    }

    /**
     * Update a domiciliation (name, is_primary)
     */
    public function update(Request $request, Domiciliation $domiciliation): JsonResponse
    {
        if ($domiciliation->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        DB::transaction(function () use ($domiciliation, $request) {
            // If setting as primary, unset other primaries
            if ($request->has('is_primary') && $request->is_primary) {
                Domiciliation::where('user_id', $domiciliation->user_id)
                    ->where('id', '!=', $domiciliation->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $data = [];
            if ($request->has('name')) {
                $data['name'] = $request->name;
            }
            if ($request->has('is_primary')) {
                $data['is_primary'] = $request->is_primary;
            }

            if (!empty($data)) {
                $domiciliation->update($data);
            }
        });

        $domiciliation->load(['address.street']);

        return $this->success(
            $this->formatDomiciliation($domiciliation),
            'Domiciliation updated successfully'
        );
    }

    /**
     * Delete a domiciliation
     * - Can delete if you are the beneficiary (user_id)
     * - Can delete if you invited this person (invited_by)
     */
    public function destroy(Domiciliation $domiciliation): JsonResponse
    {
        $userId = auth()->id();

        // Check authorization
        $canDelete = $domiciliation->user_id === $userId // Beneficiary
            || $domiciliation->invited_by === $userId; // Inviter

        if (!$canDelete) {
            return $this->error('Unauthorized', 403);
        }

        $domiciliation->delete();

        return $this->noContent();
    }

    /**
     * Generate QR code invitation to domiciliate someone at your address
     */
    public function generateInvitation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|exists:addresses,id',
            'expires_in_minutes' => 'nullable|integer|min:5|max:1440', // 5 min to 24 hours
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $addressId = $request->address_id;
        $expiresIn = $request->expires_in_minutes ?? 30;

        // Check if user is domiciliated at this address and can invite
        $myDomiciliation = Domiciliation::where('user_id', $user->id)
            ->where('address_id', $addressId)
            ->approved()
            ->first();

        if (!$myDomiciliation || !$myDomiciliation->canManageResidents()) {
            return $this->error('You must be domiciliated at this address to invite others', 403);
        }

        // Generate invitation token
        $token = Domiciliation::generateInvitationToken();
        $expiresAt = now()->addMinutes($expiresIn);

        // Store pending invitation (we'll create the actual domiciliation when scanned)
        // For now, store in cache or create a temporary record
        $invitationData = [
            'address_id' => $addressId,
            'invited_by' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        // Store in cache for later retrieval
        cache()->put("domiciliation_invitation:{$token}", $invitationData, $expiresAt);

        // Generate QR code
        $qrData = json_encode([
            'type' => 'domiciliation_invitation',
            'token' => $token,
        ]);

        $qrCodeUrl = $this->qrCodeService->generate($qrData);

        return $this->success([
            'token' => $token,
            'qrCode' => $qrCodeUrl,
            'expiresAt' => $expiresAt->toIso8601String(),
            'addressId' => $addressId,
        ], 'Invitation QR code generated successfully');
    }

    /**
     * Accept domiciliation by scanning QR code
     */
    public function acceptInvitation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
            'name' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $token = $request->token;

        // Retrieve invitation from cache
        $invitation = cache()->get("domiciliation_invitation:{$token}");

        if (!$invitation) {
            return $this->error('Invalid or expired invitation', 400);
        }

        // Check if already domiciliated at this address
        $existing = Domiciliation::where('user_id', $user->id)
            ->where('address_id', $invitation['address_id'])
            ->first();

        if ($existing) {
            // Remove the invitation from cache
            cache()->forget("domiciliation_invitation:{$token}");
            return $this->error('You are already domiciliated at this address', 409);
        }

        // Cannot invite yourself
        if ($invitation['invited_by'] === $user->id) {
            return $this->error('You cannot accept your own invitation', 400);
        }

        $domiciliation = null;

        DB::transaction(function () use ($user, $invitation, $request, &$domiciliation) {
            // If setting as primary, unset other primaries
            if ($request->is_primary) {
                Domiciliation::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $domiciliation = Domiciliation::create([
                'user_id' => $user->id,
                'address_id' => $invitation['address_id'],
                'invited_by' => $invitation['invited_by'],
                'name' => $request->name ?? 'Domicile',
                'role' => 'resident',
                'status' => 'approved',
                'is_primary' => $request->is_primary ?? false,
            ]);
        });

        // Remove the invitation from cache
        cache()->forget("domiciliation_invitation:{$token}");

        $domiciliation->load(['address.street', 'invitedBy']);

        return $this->success(
            $this->formatDomiciliation($domiciliation),
            'Domiciliation accepted successfully',
            201
        );
    }

    /**
     * Get all residents domiciliated at an address (if you are also domiciliated there)
     */
    public function getResidents(int $addressId): JsonResponse
    {
        $user = auth()->user();

        // Check if user is domiciliated at this address
        $myDomiciliation = Domiciliation::where('user_id', $user->id)
            ->where('address_id', $addressId)
            ->approved()
            ->first();

        if (!$myDomiciliation) {
            return $this->error('You must be domiciliated at this address to see residents', 403);
        }

        $residents = Domiciliation::with('user')
            ->where('address_id', $addressId)
            ->approved()
            ->get();

        return $this->success([
            'addressId' => $addressId,
            'myRole' => $myDomiciliation->role,
            'residents' => $residents->map(fn($d) => [
                'id' => $d->id,
                'userId' => $d->user_id,
                'name' => $d->name,
                'role' => $d->role,
                'isPrimary' => $d->is_primary,
                'invitedBy' => $d->invited_by,
                'createdAt' => $d->created_at->toIso8601String(),
                'user' => [
                    'id' => $d->user->id,
                    'fullName' => $d->user->full_name,
                    'email' => $myDomiciliation->canManageResidents() ? $d->user->email : null,
                    'phone' => $myDomiciliation->canManageResidents() ? $d->user->phone : null,
                ],
            ])->toArray(),
            'count' => $residents->count(),
        ]);
    }

    /**
     * Remove a resident from an address (if you invited them or are owner)
     */
    public function removeResident(int $addressId, int $domiciliationId): JsonResponse
    {
        $user = auth()->user();

        // Check if user is domiciliated at this address
        $myDomiciliation = Domiciliation::where('user_id', $user->id)
            ->where('address_id', $addressId)
            ->approved()
            ->first();

        if (!$myDomiciliation || !$myDomiciliation->canManageResidents()) {
            return $this->error('Unauthorized', 403);
        }

        $targetDomiciliation = Domiciliation::where('id', $domiciliationId)
            ->where('address_id', $addressId)
            ->first();

        if (!$targetDomiciliation) {
            return $this->error('Domiciliation not found', 404);
        }

        // Cannot remove yourself this way
        if ($targetDomiciliation->user_id === $user->id) {
            return $this->error('Use the delete endpoint to remove your own domiciliation', 400);
        }

        // Only owner or the person who invited can remove
        $canRemove = $myDomiciliation->isOwner()
            || $targetDomiciliation->invited_by === $user->id;

        if (!$canRemove) {
            return $this->error('You can only remove residents you invited', 403);
        }

        $targetDomiciliation->delete();

        return $this->success(null, 'Resident removed successfully');
    }

    // Formatting methods
    protected function formatDomiciliation(Domiciliation $domiciliation): array
    {
        return [
            'id' => $domiciliation->id,
            'name' => $domiciliation->name,
            'role' => $domiciliation->role,
            'status' => $domiciliation->status,
            'isPrimary' => $domiciliation->is_primary,
            'invitedBy' => $domiciliation->invitedBy ? [
                'id' => $domiciliation->invitedBy->id,
                'fullName' => $domiciliation->invitedBy->full_name,
            ] : null,
            'address' => $domiciliation->address ? $this->formatAddress($domiciliation->address) : null,
            'createdAt' => $domiciliation->created_at->toIso8601String(),
            'updatedAt' => $domiciliation->updated_at->toIso8601String(),
        ];
    }

    protected function formatDomiciliations($domiciliations): array
    {
        return $domiciliations->map(fn($d) => $this->formatDomiciliation($d))->toArray();
    }

    protected function formatAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'swAddress' => $address->sw_address,
            'displayName' => $address->display_name,
            'coordinates' => $address->coordinates,
            'localization' => $address->localization,
            'way' => $address->way,
            'shareUrl' => $address->getShareUrl(),
        ];
    }
}
