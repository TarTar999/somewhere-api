<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    /**
     * Request account deletion
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        /** @var User $user */
        $user = auth()->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Invalid password', 401);
        }

        // Check if deletion already requested
        if ($user->hasPendingDeletion()) {
            return $this->error('Account deletion already requested', 400, [
                'deletionScheduledAt' => $user->deletion_scheduled_at?->toIso8601String(),
            ]);
        }

        // Schedule deletion (e.g., 30 days from now to allow recovery)
        $deletionDate = now()->addDays(30);

        $user->update([
            'deletion_requested_at' => now(),
            'deletion_scheduled_at' => $deletionDate,
            'deletion_reason' => $request->reason,
        ]);

        return $this->success([
            'deletionRequestedAt' => now()->toIso8601String(),
            'deletionScheduledAt' => $deletionDate->toIso8601String(),
            'message' => 'Your account is scheduled for deletion. You can cancel this request within 30 days.',
        ], 'Account deletion requested');
    }

    /**
     * Cancel account deletion request
     */
    public function cancelDeletion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        /** @var User $user */
        $user = auth()->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Invalid password', 401);
        }

        if (!$user->hasPendingDeletion()) {
            return $this->error('No pending deletion request', 400);
        }

        $user->update([
            'deletion_requested_at' => null,
            'deletion_scheduled_at' => null,
            'deletion_reason' => null,
        ]);

        return $this->success(null, 'Account deletion cancelled');
    }

    /**
     * Immediately delete account (requires password confirmation)
     */
    public function deleteImmediately(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        /** @var User $user */
        $user = auth()->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Invalid password', 401);
        }

        // Revoke all tokens
        $this->tokenService->revokeAllTokens($user);

        // Soft delete the user
        $user->update([
            'email' => "deleted_{$user->id}_{$user->email}",
            'phone' => "deleted_{$user->id}_{$user->phone}",
            'deletion_requested_at' => now(),
            'deletion_reason' => 'Immediate deletion requested by user',
        ]);

        $user->delete(); // Soft delete

        return $this->success(null, 'Account deleted successfully');
    }

    /**
     * Get account deletion status
     */
    public function getDeletionStatus(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->hasPendingDeletion()) {
            return $this->success([
                'hasPendingDeletion' => false,
            ], 'No pending deletion');
        }

        return $this->success([
            'hasPendingDeletion' => true,
            'deletionRequestedAt' => $user->deletion_requested_at?->toIso8601String(),
            'deletionScheduledAt' => $user->deletion_scheduled_at?->toIso8601String(),
            'daysUntilDeletion' => $user->deletion_scheduled_at?->diffInDays(now()),
            'reason' => $user->deletion_reason,
        ], 'Deletion pending');
    }

    /**
     * Export user data (GDPR compliance)
     */
    public function exportData(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->load([
            'settings',
            'addresses',
            'collections.addresses',
            'payments',
            'invoices',
            'kycVerification',
            'proofOfLocations',
            'tracks',
        ]);

        $data = [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'sex' => $user->sex,
                'cniNumber' => $user->cni_number,
                'nuiNumber' => $user->nui_number,
                'createdAt' => $user->created_at->toIso8601String(),
            ],
            'settings' => $user->settings,
            'addresses' => $user->addresses->map(fn($a) => [
                'swAddress' => $a->sw_address,
                'displayName' => $a->display_name,
                'latitude' => $a->latitude,
                'longitude' => $a->longitude,
                'quarter' => $a->quarter,
                'subQuarter' => $a->sub_quarter,
                'lieuDit' => $a->lieu_dit,
                'createdAt' => $a->created_at->toIso8601String(),
            ]),
            'collections' => $user->collections->map(fn($c) => [
                'name' => $c->name,
                'type' => $c->type,
                'addressCount' => $c->addresses->count(),
            ]),
            'payments' => $user->payments->map(fn($p) => [
                'type' => $p->type,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'paidAt' => $p->paid_at?->toIso8601String(),
            ]),
            'invoices' => $user->invoices->map(fn($i) => [
                'invoiceNumber' => $i->invoice_number,
                'description' => $i->description,
                'totalAmount' => $i->total_amount,
                'currency' => $i->currency,
                'invoiceDate' => $i->invoice_date->toDateString(),
            ]),
            'proofOfLocations' => $user->proofOfLocations->map(fn($p) => [
                'documentNumber' => $p->document_number,
                'status' => $p->status,
                'issuedAt' => $p->issued_at->toIso8601String(),
                'expiresAt' => $p->expires_at->toIso8601String(),
            ]),
            'exportedAt' => now()->toIso8601String(),
        ];

        return $this->success($data, 'Data exported successfully');
    }
}
