<?php

namespace App\Services;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class KycService
{
    /**
     * Get or create KYC verification for a user
     */
    public function getOrCreateKyc(User $user): KycVerification
    {
        return KycVerification::getOrCreateForUser($user);
    }

    /**
     * Upload CNI front image
     */
    public function uploadCniFront(User $user, UploadedFile $file): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);
        $path = $this->storeDocument($file, $user, 'cni_front');

        // Delete old file if exists
        if ($kyc->cni_front_path) {
            Storage::disk('kyc')->delete($kyc->cni_front_path);
        }

        $kyc->update([
            'cni_front_path' => $path,
            'status' => $this->determineStatus($kyc->fresh()),
        ]);

        return $kyc->fresh();
    }

    /**
     * Upload CNI back image
     */
    public function uploadCniBack(User $user, UploadedFile $file): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);
        $path = $this->storeDocument($file, $user, 'cni_back');

        if ($kyc->cni_back_path) {
            Storage::disk('kyc')->delete($kyc->cni_back_path);
        }

        $kyc->update([
            'cni_back_path' => $path,
            'status' => $this->determineStatus($kyc->fresh()),
        ]);

        return $kyc->fresh();
    }

    /**
     * Upload selfie image
     */
    public function uploadSelfie(User $user, UploadedFile $file): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);
        $path = $this->storeDocument($file, $user, 'selfie');

        if ($kyc->selfie_path) {
            Storage::disk('kyc')->delete($kyc->selfie_path);
        }

        $kyc->update([
            'selfie_path' => $path,
            'status' => $this->determineStatus($kyc->fresh()),
        ]);

        return $kyc->fresh();
    }

    /**
     * Upload verification video
     */
    public function uploadVideo(User $user, UploadedFile $file): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);
        $path = $this->storeDocument($file, $user, 'video');

        if ($kyc->video_path) {
            Storage::disk('kyc')->delete($kyc->video_path);
        }

        $kyc->update([
            'video_path' => $path,
            'status' => $this->determineStatus($kyc->fresh()),
        ]);

        return $kyc->fresh();
    }

    /**
     * Mark phone as verified for KYC
     */
    public function markPhoneVerified(User $user): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);

        $kyc->update([
            'phone_verified' => true,
            'status' => $this->determineStatus($kyc->fresh()),
        ]);

        return $kyc->fresh();
    }

    /**
     * Mark address as verified for KYC
     */
    public function markAddressVerified(User $user): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);

        $kyc->update([
            'address_verified' => true,
            'status' => $this->determineStatus($kyc->fresh()),
        ]);

        return $kyc->fresh();
    }

    /**
     * Submit KYC for review
     */
    public function submitForReview(User $user): KycVerification
    {
        $kyc = $this->getOrCreateKyc($user);

        if (!$kyc->isComplete()) {
            throw new \InvalidArgumentException('KYC documents are incomplete');
        }

        $kyc->update(['status' => 'in_review']);

        return $kyc->fresh();
    }

    /**
     * Admin: Approve KYC
     */
    public function approve(KycVerification $kyc, User $reviewer, ?string $notes = null): KycVerification
    {
        $kyc->approve($reviewer, $notes);

        // Update user settings
        $kyc->update([
            'cni_verified' => true,
            'selfie_verified' => true,
        ]);

        return $kyc->fresh();
    }

    /**
     * Admin: Reject KYC
     */
    public function reject(
        KycVerification $kyc,
        User $reviewer,
        string $reason,
        ?string $notes = null
    ): KycVerification {
        $kyc->reject($reviewer, $reason, $notes);

        return $kyc->fresh();
    }

    /**
     * Format KYC for API response
     */
    public function formatKycForResponse(KycVerification $kyc): array
    {
        return [
            'id' => $kyc->id,
            'status' => $kyc->status,
            'level' => $kyc->level,
            'completionPercentage' => $kyc->getCompletionPercentage(),
            'isComplete' => $kyc->isComplete(),
            'documents' => [
                'cniFront' => [
                    'uploaded' => !empty($kyc->cni_front_path),
                    'verified' => $kyc->cni_verified,
                ],
                'cniBack' => [
                    'uploaded' => !empty($kyc->cni_back_path),
                    'verified' => $kyc->cni_verified,
                ],
                'selfie' => [
                    'uploaded' => !empty($kyc->selfie_path),
                    'verified' => $kyc->selfie_verified,
                ],
                'video' => [
                    'uploaded' => !empty($kyc->video_path),
                ],
            ],
            'verifications' => [
                'phone' => $kyc->phone_verified,
                'address' => $kyc->address_verified,
            ],
            'rejectionReason' => $kyc->rejection_reason,
            'reviewedAt' => $kyc->reviewed_at?->toISOString(),
            'approvedAt' => $kyc->approved_at?->toISOString(),
            'expiresAt' => $kyc->expires_at?->toISOString(),
            'createdAt' => $kyc->created_at->toISOString(),
            'updatedAt' => $kyc->updated_at->toISOString(),
        ];
    }

    protected function storeDocument(UploadedFile $file, User $user, string $type): string
    {
        $filename = "{$type}_{$user->id}_" . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs("kyc/{$user->id}", $filename, 'kyc');
    }

    protected function determineStatus(KycVerification $kyc): string
    {
        // If already approved or rejected, don't change status
        if (in_array($kyc->status, ['approved', 'rejected', 'in_review'])) {
            return $kyc->status;
        }

        // If all documents are uploaded, keep as pending (user can submit for review)
        return 'pending';
    }
}
