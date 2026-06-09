<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\SharedCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's own collections
        $ownCollections = Collection::where('owner_id', $user->id)
            ->withCount('addresses')
            ->with('addresses')
            ->latest()
            ->get()
            ->map(fn($collection) => $this->formatCollection($collection, 'owner'));

        // Get collections shared with user
        $sharedCollections = SharedCollection::where('shared_with_user_id', $user->id)
            ->valid()
            ->with(['collection.addresses', 'collection.owner'])
            ->get()
            ->map(function ($shared) {
                $formatted = $this->formatCollection($shared->collection, 'shared');
                $formatted['permissions'] = $shared->permissions;
                $formatted['sharedBy'] = [
                    'id' => $shared->collection->owner->id,
                    'name' => $shared->collection->owner->full_name,
                ];
                return $formatted;
            });

        return Inertia::render('collections/index', [
            'ownCollections' => $ownCollections,
            'sharedCollections' => $sharedCollections,
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();

        // Get user's addresses for selection
        $addresses = $user->addresses()
            ->with('street')
            ->latest()
            ->get()
            ->map(fn($address) => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'displayName' => $address->display_name,
                'quarter' => $address->quarter,
                'latitude' => (float) $address->latitude,
                'longitude' => (float) $address->longitude,
            ]);

        return Inertia::render('collections/create', [
            'addresses' => $addresses,
        ]);
    }

    public function show(Collection $collection): Response
    {
        $user = auth()->user();

        // Check access
        if (!$this->canAccessCollection($collection, $user->id)) {
            abort(403);
        }

        $collection->load(['addresses', 'owner']);

        $isOwner = $collection->owner_id === $user->id;
        $sharedWith = [];

        if ($isOwner) {
            $sharedWith = SharedCollection::where('collection_id', $collection->id)
                ->with('sharedWithUser')
                ->get()
                ->map(fn($shared) => [
                    'id' => $shared->id,
                    'user' => [
                        'id' => $shared->sharedWithUser->id,
                        'name' => $shared->sharedWithUser->full_name,
                        'phone' => $shared->sharedWithUser->phone,
                    ],
                    'permissions' => $shared->permissions,
                    'sharedAt' => $shared->created_at?->toIso8601String(),
                ]);
        }

        return Inertia::render('collections/show', [
            'collection' => $this->formatCollection($collection, $isOwner ? 'owner' : 'shared'),
            'isOwner' => $isOwner,
            'sharedWith' => $sharedWith,
        ]);
    }

    public function edit(Collection $collection): Response
    {
        $user = auth()->user();

        if ($collection->owner_id !== $user->id) {
            abort(403);
        }

        $collection->load('addresses');

        // Get user's addresses for selection
        $addresses = $user->addresses()
            ->with('street')
            ->latest()
            ->get()
            ->map(fn($address) => [
                'id' => $address->id,
                'swAddress' => $address->sw_address,
                'displayName' => $address->display_name,
                'quarter' => $address->quarter,
                'latitude' => (float) $address->latitude,
                'longitude' => (float) $address->longitude,
            ]);

        return Inertia::render('collections/edit', [
            'collection' => $this->formatCollection($collection, 'owner'),
            'addresses' => $addresses,
        ]);
    }

    protected function canAccessCollection(Collection $collection, int $userId): bool
    {
        if ($collection->owner_id === $userId) {
            return true;
        }

        return SharedCollection::where('collection_id', $collection->id)
            ->where('shared_with_user_id', $userId)
            ->valid()
            ->exists();
    }

    protected function formatCollection(Collection $collection, string $role = 'owner'): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'icon' => $collection->icon,
            'color' => $collection->color,
            'type' => $collection->type,
            'role' => $role,
            'addressCount' => $collection->addresses_count ?? $collection->addresses->count(),
            'addresses' => $collection->relationLoaded('addresses')
                ? $collection->addresses->map(fn($a) => [
                    'id' => $a->id,
                    'swAddress' => $a->sw_address,
                    'displayName' => $a->display_name,
                    'quarter' => $a->quarter,
                    'latitude' => (float) $a->latitude,
                    'longitude' => (float) $a->longitude,
                    'verificationStatus' => $a->verification_status,
                ])
                : [],
            'createdAt' => $collection->created_at?->toIso8601String(),
        ];
    }
}
