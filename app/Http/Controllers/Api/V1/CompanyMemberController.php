<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyMemberController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    public function index(): JsonResponse
    {
        $company = auth()->user()->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        $members = $company->users()
            ->withPivot(['role', 'status', 'joined_at'])
            ->get()
            ->map(fn ($user) => $this->formatMember($user));

        return $this->success($members);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:100',
        ]);

        $company = auth()->user()->currentCompany;

        $users = $this->companyService->searchUsers(
            $request->query('query'),
            $company
        );

        $results = $users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar_path ? asset('storage/' . $user->avatar_path) : null,
        ]);

        return $this->success($results);
    }

    public function invite(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserManager($user)) {
            return $this->error('Only managers and admins can invite members', 403);
        }

        $request->validate([
            'emailOrPhone' => 'required|string|max:100',
            'role' => 'required|in:admin,manager,member',
        ]);

        // Only admins can invite other admins
        if ($request->role === 'admin' && !$company->isUserAdmin($user)) {
            return $this->error('Only admins can invite other admins', 403);
        }

        try {
            $membership = $this->companyService->inviteMember(
                $company,
                $request->emailOrPhone,
                $request->role,
                $user
            );

            return $this->success([
                'invitationToken' => $membership->invitation_token,
                'expiresAt' => $membership->invitation_expires_at->toIso8601String(),
            ], 'Invitation sent successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function acceptInvitation(string $token): JsonResponse
    {
        try {
            $membership = $this->companyService->acceptInvitation($token, auth()->user());

            return $this->success([
                'company' => [
                    'id' => $membership->company_id,
                    'name' => $membership->company->name,
                ],
                'role' => $membership->role,
            ], 'Invitation accepted');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateRole(Request $request, User $member): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can change roles', 403);
        }

        $request->validate([
            'role' => 'required|in:admin,manager,member',
        ]);

        try {
            $this->companyService->changeRole($company, $member, $request->role);

            return $this->success(null, 'Role updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function remove(User $member): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        if (!$company->isUserAdmin($user)) {
            return $this->error('Only admins can remove members', 403);
        }

        if ($member->id === $user->id) {
            return $this->error('Cannot remove yourself', 400);
        }

        try {
            $this->companyService->removeMember($company, $member);

            return $this->success(null, 'Member removed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    protected function formatMember(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar_path ? asset('storage/' . $user->avatar_path) : null,
            'role' => $user->pivot->role,
            'status' => $user->pivot->status,
            'joinedAt' => $user->pivot->joined_at?->toIso8601String(),
        ];
    }
}
