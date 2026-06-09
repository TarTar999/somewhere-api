<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get sent deliveries
        $sent = DeliveryRequest::where('initiator_id', $user->id)
            ->with(['recipient', 'pickupAddress', 'deliveryAddress'])
            ->latest()
            ->get()
            ->map(fn($r) => $this->formatDeliveryRequest($r, 'sent'));

        // Get received deliveries
        $received = DeliveryRequest::where('recipient_id', $user->id)
            ->with(['initiator', 'pickupAddress', 'deliveryAddress'])
            ->latest()
            ->get()
            ->map(fn($r) => $this->formatDeliveryRequest($r, 'received'));

        // Stats
        $stats = [
            'totalSent' => $sent->count(),
            'totalReceived' => $received->count(),
            'pending' => $sent->where('status', 'pending')->count() + $received->where('status', 'pending')->count(),
            'inProgress' => $sent->whereIn('status', ['accepted', 'in_progress'])->count() + $received->whereIn('status', ['accepted', 'in_progress'])->count(),
            'completed' => $sent->where('status', 'completed')->count() + $received->where('status', 'completed')->count(),
        ];

        // Get user's addresses for creating new requests
        $addresses = $user->addresses()
            ->with('street')
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'swAddress' => $a->sw_address,
                'displayName' => $a->display_name,
                'quarter' => $a->quarter,
            ]);

        return Inertia::render('deliveries/index', [
            'sent' => $sent,
            'received' => $received,
            'stats' => $stats,
            'addresses' => $addresses,
        ]);
    }

    public function show(DeliveryRequest $delivery): Response
    {
        $user = auth()->user();

        if (!$delivery->isParticipant($user)) {
            abort(403);
        }

        $delivery->load(['initiator', 'recipient', 'pickupAddress', 'deliveryAddress']);

        $role = $delivery->initiator_id === $user->id ? 'sent' : 'received';

        return Inertia::render('deliveries/show', [
            'delivery' => $this->formatDeliveryRequest($delivery, $role),
            'isInitiator' => $delivery->isInitiator($user),
        ]);
    }

    protected function formatDeliveryRequest(DeliveryRequest $request, string $role): array
    {
        return [
            'id' => $request->id,
            'title' => $request->title,
            'description' => $request->description,
            'value' => $request->value,
            'currency' => $request->currency,
            'status' => $request->status,
            'role' => $role,
            'initiatorConfirmed' => $request->initiator_confirmed,
            'recipientConfirmed' => $request->recipient_confirmed,
            'shareUrl' => $request->share_url,
            'shareToken' => $request->share_token,
            'initiator' => $request->initiator ? [
                'id' => $request->initiator->id,
                'name' => $request->initiator->full_name,
                'phone' => $request->initiator->phone,
            ] : null,
            'recipient' => $request->recipient ? [
                'id' => $request->recipient->id,
                'name' => $request->recipient->full_name,
                'phone' => $request->recipient->phone,
            ] : null,
            'pickupAddress' => $request->pickupAddress ? [
                'id' => $request->pickupAddress->id,
                'swAddress' => $request->pickupAddress->sw_address,
                'displayName' => $request->pickupAddress->display_name,
                'latitude' => (float) $request->pickupAddress->latitude,
                'longitude' => (float) $request->pickupAddress->longitude,
            ] : null,
            'deliveryAddress' => $request->deliveryAddress ? [
                'id' => $request->deliveryAddress->id,
                'swAddress' => $request->deliveryAddress->sw_address,
                'displayName' => $request->deliveryAddress->display_name,
                'latitude' => (float) $request->deliveryAddress->latitude,
                'longitude' => (float) $request->deliveryAddress->longitude,
            ] : null,
            'deliveryCoordinates' => $request->delivery_latitude ? [
                'latitude' => (float) $request->delivery_latitude,
                'longitude' => (float) $request->delivery_longitude,
            ] : null,
            'createdAt' => $request->created_at?->toIso8601String(),
            'acceptedAt' => $request->accepted_at?->toIso8601String(),
            'completedAt' => $request->completed_at?->toIso8601String(),
        ];
    }
}
