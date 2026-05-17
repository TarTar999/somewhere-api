<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Collection\AddAddressRequest;
use App\Models\Address;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;

class CollectionAddressController extends Controller
{
    public function add(AddAddressRequest $request, Collection $collection): JsonResponse
    {
        if ($collection->owner_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $address = Address::find($request->addressId);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        // Check if address is already in collection
        if ($collection->addresses()->where('address_id', $address->id)->exists()) {
            return $this->error('Address already in collection', 409);
        }

        // Get max order
        $maxOrder = $collection->addresses()->max('order') ?? 0;

        $collection->addresses()->attach($address->id, ['order' => $maxOrder + 1]);

        $collection->load('addresses');

        return $this->success($this->formatCollection($collection), 'Address added to collection');
    }

    public function remove(Collection $collection, Address $address): JsonResponse
    {
        if ($collection->owner_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        if (!$collection->addresses()->where('address_id', $address->id)->exists()) {
            return $this->error('Address not in collection', 404);
        }

        $collection->addresses()->detach($address->id);

        $collection->load('addresses');

        return $this->success($this->formatCollection($collection), 'Address removed from collection');
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
        ];
    }
}
