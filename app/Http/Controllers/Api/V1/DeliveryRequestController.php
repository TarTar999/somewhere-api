<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DeliveryRequest\AcceptDeliveryRequest;
use App\Http\Requests\Api\DeliveryRequest\StoreDeliveryRequest;
use App\Http\Requests\Api\DeliveryRequest\UpdateStatusRequest;
use App\Models\DeliveryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryRequestController extends Controller
{
    /**
     * List delivery requests for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->input('type', 'all');
        $statuses = $request->input('status') ? explode(',', $request->input('status')) : null;

        $query = DeliveryRequest::with(['initiator', 'recipient', 'pickupAddress', 'deliveryAddress']);

        // Filter by type
        if ($type === 'sent') {
            $query->sent($user);
        } elseif ($type === 'received') {
            $query->received($user);
        } else {
            $query->forUser($user);
        }

        // Filter by status
        if ($statuses) {
            $query->withStatus($statuses);
        }

        $requests = $query->orderBy('updated_at', 'desc')->get();

        return $this->success($this->formatDeliveryRequests($requests));
    }

    /**
     * Create a new delivery request
     */
    public function store(StoreDeliveryRequest $request): JsonResponse
    {
        $user = $request->user();

        $deliveryRequest = DeliveryRequest::create([
            'initiator_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'value' => $request->value,
            'currency' => $request->currency,
            'pickup_address_id' => $request->pickup_address_id,
        ]);

        $deliveryRequest->load(['initiator', 'pickupAddress']);

        return $this->success($this->formatDeliveryRequest($deliveryRequest), 'Demande de livraison creee', 201);
    }

    /**
     * Get a specific delivery request
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $deliveryRequest = DeliveryRequest::with(['initiator', 'recipient', 'pickupAddress', 'deliveryAddress'])
            ->findOrFail($id);

        if (!$deliveryRequest->isParticipant($user)) {
            return $this->error('Non autorise', 403);
        }

        return $this->success($this->formatDeliveryRequest($deliveryRequest));
    }

    /**
     * Get delivery request by share token (PUBLIC - no auth required)
     */
    public function showByToken(string $token): JsonResponse
    {
        $deliveryRequest = DeliveryRequest::where('share_token', $token)
            ->with(['initiator'])
            ->first();

        if (!$deliveryRequest) {
            return $this->error('Token invalide', 404);
        }

        if (!$deliveryRequest->isPending()) {
            return $this->error('Cette demande a deja ete traitee', 400);
        }

        // Return limited data for public access
        return $this->success([
            'id' => $deliveryRequest->id,
            'title' => $deliveryRequest->title,
            'description' => $deliveryRequest->description,
            'value' => $deliveryRequest->value,
            'currency' => $deliveryRequest->currency,
            'status' => $deliveryRequest->status,
            'initiator' => [
                'id' => $deliveryRequest->initiator->id,
                'firstName' => $deliveryRequest->initiator->first_name,
                'lastName' => substr($deliveryRequest->initiator->last_name, 0, 1) . '.',
            ],
            'createdAt' => $deliveryRequest->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Accept a delivery request
     */
    public function accept(AcceptDeliveryRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $deliveryRequest = DeliveryRequest::findOrFail($id);

        if (!$deliveryRequest->isPending()) {
            return $this->error('Cette demande a deja ete acceptee', 400);
        }

        if ($deliveryRequest->isInitiator($user)) {
            return $this->error('Vous ne pouvez pas accepter votre propre demande', 403);
        }

        $addressId = $request->address_id;
        $latitude = $request->input('location.latitude');
        $longitude = $request->input('location.longitude');

        if (!$deliveryRequest->accept($user, $addressId, $latitude, $longitude)) {
            return $this->error('Impossible d\'accepter cette demande', 400);
        }

        $deliveryRequest->load(['initiator', 'recipient', 'pickupAddress', 'deliveryAddress']);

        // TODO: Send push notification to initiator

        return $this->success($this->formatDeliveryRequest($deliveryRequest), 'Demande acceptee');
    }

    /**
     * Update delivery request status
     */
    public function updateStatus(UpdateStatusRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $newStatus = $request->status;

        $deliveryRequest = DeliveryRequest::findOrFail($id);

        if (!$deliveryRequest->isParticipant($user)) {
            return $this->error('Non autorise', 403);
        }

        if (!$deliveryRequest->canTransitionTo($newStatus, $user)) {
            return $this->error('Transition de statut invalide', 400);
        }

        $deliveryRequest->status = $newStatus;
        $deliveryRequest->save();

        $deliveryRequest->load(['initiator', 'recipient', 'pickupAddress', 'deliveryAddress']);

        // TODO: Send push notification

        return $this->success($this->formatDeliveryRequest($deliveryRequest), 'Statut mis a jour');
    }

    /**
     * Confirm delivery completion
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $deliveryRequest = DeliveryRequest::findOrFail($id);

        if (!$deliveryRequest->isParticipant($user)) {
            return $this->error('Non autorise', 403);
        }

        if (!$deliveryRequest->isInProgress()) {
            return $this->error('La demande doit etre en cours pour etre confirmee', 400);
        }

        if (!$deliveryRequest->confirm($user)) {
            return $this->error('Impossible de confirmer', 400);
        }

        $deliveryRequest->load(['initiator', 'recipient', 'pickupAddress', 'deliveryAddress']);

        // TODO: Send push notification

        return $this->success($this->formatDeliveryRequest($deliveryRequest), 'Confirmation enregistree');
    }

    /**
     * Delete/Cancel a delivery request
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $deliveryRequest = DeliveryRequest::findOrFail($id);

        if (!$deliveryRequest->isParticipant($user)) {
            return $this->error('Non autorise', 403);
        }

        if ($deliveryRequest->isCompleted()) {
            return $this->error('Impossible de supprimer une demande terminee', 400);
        }

        if ($deliveryRequest->isPending()) {
            $deliveryRequest->delete();
        } else {
            $deliveryRequest->status = 'cancelled';
            $deliveryRequest->save();
        }

        return $this->success(null, 'Demande supprimee');
    }

    /**
     * Format a single delivery request
     */
    protected function formatDeliveryRequest(DeliveryRequest $request): array
    {
        return [
            'id' => $request->id,
            'initiatorId' => $request->initiator_id,
            'recipientId' => $request->recipient_id,
            'title' => $request->title,
            'description' => $request->description,
            'value' => $request->value,
            'currency' => $request->currency,
            'status' => $request->status,
            'initiatorConfirmed' => $request->initiator_confirmed,
            'recipientConfirmed' => $request->recipient_confirmed,
            'pickupAddressId' => $request->pickup_address_id,
            'deliveryAddressId' => $request->delivery_address_id,
            'deliveryLatitude' => $request->delivery_latitude,
            'deliveryLongitude' => $request->delivery_longitude,
            'shareToken' => $request->share_token,
            'shareUrl' => $request->share_url,
            'initiator' => $request->initiator ? [
                'id' => $request->initiator->id,
                'firstName' => $request->initiator->first_name,
                'lastName' => $request->initiator->last_name,
                'email' => $request->initiator->email,
                'phone' => $request->initiator->phone,
            ] : null,
            'recipient' => $request->recipient ? [
                'id' => $request->recipient->id,
                'firstName' => $request->recipient->first_name,
                'lastName' => $request->recipient->last_name,
                'email' => $request->recipient->email,
                'phone' => $request->recipient->phone,
            ] : null,
            'pickupAddress' => $request->pickupAddress ? [
                'id' => $request->pickupAddress->id,
                'swAddress' => $request->pickupAddress->sw_address,
                'displayName' => $request->pickupAddress->display_name,
                'latLon' => $request->pickupAddress->lat_lon,
            ] : null,
            'deliveryAddress' => $request->deliveryAddress ? [
                'id' => $request->deliveryAddress->id,
                'swAddress' => $request->deliveryAddress->sw_address,
                'displayName' => $request->deliveryAddress->display_name,
                'latLon' => $request->deliveryAddress->lat_lon,
            ] : null,
            'createdAt' => $request->created_at?->toIso8601String(),
            'updatedAt' => $request->updated_at?->toIso8601String(),
            'acceptedAt' => $request->accepted_at?->toIso8601String(),
            'completedAt' => $request->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Format multiple delivery requests
     */
    protected function formatDeliveryRequests($requests): array
    {
        return $requests->map(fn($r) => $this->formatDeliveryRequest($r))->toArray();
    }
}
