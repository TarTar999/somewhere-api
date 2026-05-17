<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\KycService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}

    /**
     * List all KYC verifications
     */
    public function index(Request $request)
    {
        $query = KycVerification::with('user')
            ->orderByDesc('created_at');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $verifications = $query->paginate(20);

        return Inertia::render('Admin/Kyc/Index', [
            'verifications' => $verifications,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Show single KYC verification
     */
    public function show(KycVerification $kyc)
    {
        $kyc->load(['user', 'reviewer']);

        return Inertia::render('Admin/Kyc/Show', [
            'kyc' => $kyc,
            'documents' => [
                'cniFront' => $kyc->cni_front_path ? asset('storage/' . $kyc->cni_front_path) : null,
                'cniBack' => $kyc->cni_back_path ? asset('storage/' . $kyc->cni_back_path) : null,
                'selfie' => $kyc->selfie_path ? asset('storage/' . $kyc->selfie_path) : null,
                'video' => $kyc->video_path ? asset('storage/' . $kyc->video_path) : null,
            ],
        ]);
    }

    /**
     * Approve KYC verification
     */
    public function approve(KycVerification $kyc, Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($kyc->isApproved()) {
            return back()->with('error', 'KYC already approved');
        }

        $this->kycService->approve($kyc, auth()->user(), $request->notes);

        return back()->with('success', 'KYC approved successfully');
    }

    /**
     * Reject KYC verification
     */
    public function reject(KycVerification $kyc, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($kyc->isRejected()) {
            return back()->with('error', 'KYC already rejected');
        }

        $this->kycService->reject($kyc, auth()->user(), $request->reason, $request->notes);

        return back()->with('success', 'KYC rejected');
    }

    /**
     * Get pending KYC count (for dashboard)
     */
    public static function getPendingCount(): int
    {
        return KycVerification::where('status', 'in_review')->count();
    }
}
