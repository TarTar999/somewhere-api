<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\KycVerification;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}

    /**
     * Get current KYC status
     */
    public function getStatus(): JsonResponse
    {
        $user = auth()->user();
        $kyc = $this->kycService->getOrCreateKyc($user);

        return $this->success(
            $this->kycService->formatKycForResponse($kyc),
            'KYC status retrieved'
        );
    }

    /**
     * Upload CNI front image
     */
    public function uploadCniFront(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $kyc = $this->kycService->uploadCniFront($user, $request->file('image'));

        return $this->success(
            $this->kycService->formatKycForResponse($kyc),
            'CNI front uploaded successfully'
        );
    }

    /**
     * Upload CNI back image
     */
    public function uploadCniBack(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $kyc = $this->kycService->uploadCniBack($user, $request->file('image'));

        return $this->success(
            $this->kycService->formatKycForResponse($kyc),
            'CNI back uploaded successfully'
        );
    }

    /**
     * Upload selfie image
     */
    public function uploadSelfie(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $kyc = $this->kycService->uploadSelfie($user, $request->file('image'));

        return $this->success(
            $this->kycService->formatKycForResponse($kyc),
            'Selfie uploaded successfully'
        );
    }

    /**
     * Upload verification video
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|mimetypes:video/mp4,video/quicktime|max:51200', // Max 50MB
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $kyc = $this->kycService->uploadVideo($user, $request->file('video'));

        return $this->success(
            $this->kycService->formatKycForResponse($kyc),
            'Video uploaded successfully'
        );
    }

    /**
     * Submit KYC for review
     */
    public function submit(): JsonResponse
    {
        $user = auth()->user();
        $kyc = $this->kycService->getOrCreateKyc($user);

        if (!$kyc->isComplete()) {
            return $this->error('Please complete all required documents before submitting', 400, [
                'completionPercentage' => $kyc->getCompletionPercentage(),
                'missing' => $this->getMissingDocuments($kyc),
            ]);
        }

        if ($kyc->status === 'in_review') {
            return $this->error('KYC is already under review', 400);
        }

        if ($kyc->isApproved()) {
            return $this->error('KYC is already approved', 400);
        }

        $kyc = $this->kycService->submitForReview($user);

        return $this->success(
            $this->kycService->formatKycForResponse($kyc),
            'KYC submitted for review'
        );
    }

    /**
     * Get missing documents list
     */
    protected function getMissingDocuments(KycVerification $kyc): array
    {
        $missing = [];

        if (empty($kyc->cni_front_path)) {
            $missing[] = 'cni_front';
        }
        if (empty($kyc->cni_back_path)) {
            $missing[] = 'cni_back';
        }
        if (empty($kyc->selfie_path)) {
            $missing[] = 'selfie';
        }
        if (!$kyc->phone_verified) {
            $missing[] = 'phone_verification';
        }

        return $missing;
    }
}
