<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    public function index(): Response
    {
        $company = auth()->user()->currentCompany;

        $members = $company->users()
            ->withPivot(['role', 'status', 'joined_at', 'invited_by'])
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar_path ? asset('storage/' . $user->avatar_path) : null,
                'role' => $user->pivot->role,
                'status' => $user->pivot->status,
                'joinedAt' => $user->pivot->joined_at?->diffForHumans(),
            ]);

        return Inertia::render('company/members/index', [
            'members' => $members,
            'canManageMembers' => auth()->user()->isCompanyManager($company),
            'canChangeRoles' => auth()->user()->isCompanyAdmin($company),
            'canAddMore' => $company->canAddMember(),
            'memberLimit' => $company->activeSubscription?->max_members ?? 0,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('company/members/invite');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'emailOrPhone' => 'required|string|max:100',
            'role' => 'required|in:admin,manager,member',
        ]);

        $user = auth()->user();
        $company = $user->currentCompany;

        // Only admins can invite other admins
        if ($request->role === 'admin' && !$company->isUserAdmin($user)) {
            return back()->withErrors(['role' => 'Only admins can invite other admins']);
        }

        try {
            $this->companyService->inviteMember(
                $company,
                $request->emailOrPhone,
                $request->role,
                $user
            );

            return redirect()->route('company.members.index')
                ->with('success', 'Invitation envoyée avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['emailOrPhone' => $e->getMessage()]);
        }
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|in:admin,manager,member',
        ]);

        $company = auth()->user()->currentCompany;

        try {
            $this->companyService->changeRole($company, $user, $request->role);

            return back()->with('success', 'Rôle mis à jour');
        } catch (\Exception $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        $company = auth()->user()->currentCompany;

        if ($user->id === auth()->id()) {
            return back()->withErrors(['member' => 'Vous ne pouvez pas vous retirer vous-même']);
        }

        try {
            $this->companyService->removeMember($company, $user);

            return back()->with('success', 'Membre retiré');
        } catch (\Exception $e) {
            return back()->withErrors(['member' => $e->getMessage()]);
        }
    }
}
