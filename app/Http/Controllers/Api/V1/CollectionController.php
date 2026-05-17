<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Collection\ShareCollectionRequest;
use App\Http\Requests\Api\Collection\StoreCollectionRequest;
use App\Http\Requests\Api\Collection\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\SharedCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    public function index(): JsonResponse
    {
        $collections = auth()->user()->collections()
            ->with('addresses')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($this->formatCollections($collections));
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = Collection::create([
            'owner_id' => auth()->id(),
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'icon' => $request->icon,
            'color' => $request->color,
            'type' => $request->type ?? 'custom',
        ]);

        return $this->success($this->formatCollection($collection), 'Collection created successfully', 201);
    }

    public function storeForUser(StoreCollectionRequest $request, User $user): JsonResponse
    {
        // Ensure user can only create collections for themselves
        if (auth()->id() !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        $collection = Collection::create([
            'owner_id' => $user->id,
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'icon' => $request->icon,
            'color' => $request->color,
            'type' => $request->type ?? 'custom',
        ]);

        return $this->success($this->formatCollection($collection), 'Collection created successfully', 201);
    }

    public function show(Collection $collection): JsonResponse
    {
        // Check ownership or shared access
        if (!$this->canAccessCollection($collection)) {
            return $this->error('Unauthorized', 403);
        }

        $collection->load('addresses');

        return $this->success($this->formatCollection($collection));
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        if ($collection->owner_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->name;
        }
        if ($request->has('description')) {
            $data['description'] = $request->description;
        }
        if ($request->has('icon')) {
            $data['icon'] = $request->icon;
        }
        if ($request->has('color')) {
            $data['color'] = $request->color;
        }

        $collection->update($data);

        return $this->success($this->formatCollection($collection), 'Collection updated successfully');
    }

    public function destroy(Collection $collection): JsonResponse
    {
        if ($collection->owner_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $collection->delete();

        return $this->noContent();
    }

    public function share(ShareCollectionRequest $request, Collection $collection): JsonResponse
    {
        if ($collection->owner_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $recipient = User::where('email', $request->recipientEmail)->first();

        if (!$recipient) {
            return $this->error('User not found', 404);
        }

        if ($recipient->id === auth()->id()) {
            return $this->error('Cannot share with yourself', 400);
        }

        // Check if already shared
        $existing = SharedCollection::where('collection_id', $collection->id)
            ->where('shared_with_user_id', $recipient->id)
            ->first();

        if ($existing) {
            $existing->update(['permissions' => $request->permissions]);
        } else {
            SharedCollection::create([
                'collection_id' => $collection->id,
                'shared_with_user_id' => $recipient->id,
                'permissions' => $request->permissions,
            ]);
        }

        return $this->success(null, 'Collection shared successfully');
    }

    public function getShared(): JsonResponse
    {
        $sharedCollections = SharedCollection::where('shared_with_user_id', auth()->id())
            ->valid()
            ->with(['collection.addresses', 'collection.owner'])
            ->get()
            ->map(function ($shared) {
                $collection = $shared->collection;
                $formatted = $this->formatCollection($collection);
                $formatted['permissions'] = $shared->permissions;
                $formatted['sharedBy'] = [
                    'id' => $collection->owner->id,
                    'name' => $collection->owner->full_name,
                    'email' => $collection->owner->email,
                ];
                return $formatted;
            });

        return $this->success($sharedCollections);
    }

    protected function canAccessCollection(Collection $collection): bool
    {
        if ($collection->owner_id === auth()->id()) {
            return true;
        }

        return SharedCollection::where('collection_id', $collection->id)
            ->where('shared_with_user_id', auth()->id())
            ->valid()
            ->exists();
    }

    protected function formatCollection(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'logo' => $collection->logo,
            'icon' => $collection->icon,
            'color' => $collection->color,
            'type' => $collection->type,
            'ownerId' => $collection->owner_id,
            'addresses' => $collection->addresses?->map(fn($a) => [
                'id' => $a->id,
                'swAddress' => $a->sw_address,
                'displayName' => $a->display_name,
                'latLon' => $a->lat_lon,
                'verificationStatus' => $a->verification_status,
            ]),
            'createdAt' => $collection->created_at?->toISOString(),
            'updatedAt' => $collection->updated_at?->toISOString(),
        ];
    }

    protected function formatCollections($collections): array
    {
        return $collections->map(fn($c) => $this->formatCollection($c))->toArray();
    }
}
