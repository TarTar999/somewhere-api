<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Support\Str;

class CompanyService
{
    public function create(array $data, User $owner): Company
    {
        $company = Company::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(6),
            'legal_name' => $data['legal_name'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? 'CM',
            'status' => Company::STATUS_PENDING,
        ]);

        // Add owner as admin
        $company->users()->attach($owner->id, [
            'role' => CompanyUser::ROLE_ADMIN,
            'status' => CompanyUser::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // Set as current company for owner
        $owner->update(['current_company_id' => $company->id]);

        return $company;
    }

    public function update(Company $company, array $data): Company
    {
        $company->update([
            'name' => $data['name'] ?? $company->name,
            'legal_name' => $data['legal_name'] ?? $company->legal_name,
            'registration_number' => $data['registration_number'] ?? $company->registration_number,
            'tax_id' => $data['tax_id'] ?? $company->tax_id,
            'email' => $data['email'] ?? $company->email,
            'phone' => $data['phone'] ?? $company->phone,
            'description' => $data['description'] ?? $company->description,
            'address' => $data['address'] ?? $company->address,
            'city' => $data['city'] ?? $company->city,
        ]);

        return $company->fresh();
    }

    public function inviteMember(Company $company, string $emailOrPhone, string $role, User $invitedBy): ?CompanyUser
    {
        // Check if company can add more members
        if (!$company->canAddMember()) {
            throw new \Exception('Company has reached maximum member limit');
        }

        // Find user by email or phone
        $user = User::where('email', $emailOrPhone)
            ->orWhere('phone', $this->normalizePhone($emailOrPhone))
            ->first();

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Check if user is already a member
        $existingMembership = $company->users()->where('user_id', $user->id)->first();
        if ($existingMembership) {
            if ($existingMembership->pivot->status === 'active') {
                throw new \Exception('User is already a member');
            }
            // Reactivate suspended membership
            if ($existingMembership->pivot->status === 'suspended') {
                $company->users()->updateExistingPivot($user->id, [
                    'status' => CompanyUser::STATUS_PENDING,
                    'role' => $role,
                    'invited_by' => $invitedBy->id,
                    'invitation_token' => Str::random(64),
                    'invitation_expires_at' => now()->addDays(7),
                ]);
                return CompanyUser::where('company_id', $company->id)
                    ->where('user_id', $user->id)
                    ->first();
            }
        }

        // Create invitation
        $company->users()->attach($user->id, [
            'role' => $role,
            'status' => CompanyUser::STATUS_PENDING,
            'invited_by' => $invitedBy->id,
            'invitation_token' => Str::random(64),
            'invitation_expires_at' => now()->addDays(7),
        ]);

        return CompanyUser::where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function acceptInvitation(string $token, User $user): CompanyUser
    {
        $membership = CompanyUser::where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            throw new \Exception('Invalid invitation');
        }

        if ($membership->isInvitationExpired()) {
            throw new \Exception('Invitation has expired');
        }

        $membership->activate();

        // Set as current company if user doesn't have one
        if (!$user->current_company_id) {
            $user->update(['current_company_id' => $membership->company_id]);
        }

        return $membership;
    }

    public function removeMember(Company $company, User $user): void
    {
        // Cannot remove the last admin
        if ($company->isUserAdmin($user) && $company->admins()->count() <= 1) {
            throw new \Exception('Cannot remove the last admin');
        }

        $company->users()->detach($user->id);

        // Clear current company if it was this one
        if ($user->current_company_id === $company->id) {
            $nextCompany = $user->activeCompanies()->first();
            $user->update(['current_company_id' => $nextCompany?->id]);
        }
    }

    public function changeRole(Company $company, User $user, string $newRole): void
    {
        if (!in_array($newRole, CompanyUser::$roles)) {
            throw new \Exception('Invalid role');
        }

        // Cannot demote the last admin
        if ($company->isUserAdmin($user) && $newRole !== 'admin' && $company->admins()->count() <= 1) {
            throw new \Exception('Cannot demote the last admin');
        }

        $company->users()->updateExistingPivot($user->id, ['role' => $newRole]);
    }

    public function transferOwnership(Company $company, User $newOwner): void
    {
        // Ensure new owner is a member
        $membership = $company->users()->where('user_id', $newOwner->id)->first();
        if (!$membership || $membership->pivot->status !== 'active') {
            throw new \Exception('User is not an active member');
        }

        // Make new owner an admin
        $company->users()->updateExistingPivot($newOwner->id, ['role' => CompanyUser::ROLE_ADMIN]);
    }

    public function searchUsers(string $query, ?Company $excludeCompany = null): \Illuminate\Database\Eloquent\Collection
    {
        $normalizedPhone = $this->normalizePhone($query);

        $users = User::where(function ($q) use ($query, $normalizedPhone) {
            $q->where('email', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$normalizedPhone}%")
              ->orWhere('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%");
        });

        if ($excludeCompany) {
            $existingUserIds = $excludeCompany->users()->pluck('users.id');
            $users->whereNotIn('id', $existingUserIds);
        }

        return $users->limit(10)->get();
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (preg_match('/^[67]\d{8}$/', $phone)) {
            $phone = '+237' . $phone;
        }

        return $phone;
    }
}
