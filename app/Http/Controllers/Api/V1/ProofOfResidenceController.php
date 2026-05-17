<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use App\Services\ProofOfResidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProofOfResidenceController extends Controller
{
    public function __construct(
        protected ProofOfResidenceService $proofService
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'addressId' => 'required|exists:addresses,id',
        ]);

        $address = Address::find($request->addressId);

        if ($address->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        if ($address->verification_status !== 'approved') {
            return $this->error('Address must be verified to generate proof of residence', 400);
        }

        $user = auth()->user();
        $result = $this->proofService->generate($user, $address);

        return $this->success([
            'url' => route('api.proof-of-residence.download', ['path' => $result['path']]),
            'documentNumber' => $result['document_number'],
            'generatedAt' => $result['generated_at'],
        ], 'Proof of residence generated successfully');
    }

    public function download(Request $request)
    {
        $path = $request->query('path');

        if (!$path) {
            return response()->json(['message' => 'Path required'], 400);
        }

        $download = $this->proofService->download($path);

        if (!$download) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return $download;
    }
}
