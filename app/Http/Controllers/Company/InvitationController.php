<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    public function show(string $token): Response
    {
        $membership = CompanyUser::where('invitation_token', $token)
            ->with('company')
            ->first();

        if (!$membership) {
            return Inertia::render('company/invitation', [
                'error' => 'Invitation invalide ou expirée',
                'invitation' => null,
            ]);
        }

        if ($membership->isInvitationExpired()) {
            return Inertia::render('company/invitation', [
                'error' => 'Cette invitation a expiré',
                'invitation' => null,
            ]);
        }

        return Inertia::render('company/invitation', [
            'invitation' => [
                'token' => $token,
                'company' => [
                    'id' => $membership->company->id,
                    'name' => $membership->company->name,
                    'logo' => $membership->company->logo_path ? asset('storage/' . $membership->company->logo_path) : null,
                ],
                'role' => $membership->role,
                'expiresAt' => $membership->invitation_expires_at->diffForHumans(),
            ],
        ]);
    }

    public function accept(string $token): RedirectResponse
    {
        try {
            $this->companyService->acceptInvitation($token, auth()->user());

            return redirect()->route('company.dashboard')
                ->with('success', 'Vous avez rejoint l\'entreprise avec succès');
        } catch (\Exception $e) {
            return redirect()->route('company.select')
                ->withErrors(['invitation' => $e->getMessage()]);
        }
    }
}
