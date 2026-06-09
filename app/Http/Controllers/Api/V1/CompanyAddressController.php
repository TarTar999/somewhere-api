<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        // Get all member IDs
        $memberIds = $company->members()->pluck('users.id');

        // Get all addresses from members
        $addresses = Address::whereIn('user_id', $memberIds)
            ->with(['user:id,first_name,last_name,email', 'street'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $formattedAddresses = collect($addresses->items())->map(fn ($address) => $this->formatAddress($address));

        return $this->paginated(
            $addresses->setCollection(collect($formattedAddresses)),
            'Company addresses retrieved'
        );
    }

    public function show(Address $address): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        // Check if address belongs to a company member
        $memberIds = $company->members()->pluck('users.id');
        if (!$memberIds->contains($address->user_id)) {
            return $this->error('Address not found in company', 404);
        }

        return $this->success($this->formatAddress($address, true));
    }

    public function search(Request $request): JsonResponse
    {
        $user = auth()->user();
        $company = $user->currentCompany;

        if (!$company) {
            return $this->error('No company selected', 404);
        }

        $request->validate([
            'query' => 'required|string|min:2|max:100',
        ]);

        $query = $request->query('query');
        $memberIds = $company->members()->pluck('users.id');

        $addresses = Address::whereIn('user_id', $memberIds)
            ->where(function ($q) use ($query) {
                $q->where('sw_address', 'like', "%{$query}%")
                  ->orWhere('lieu_dit', 'like', "%{$query}%")
                  ->orWhere('quarter', 'like', "%{$query}%")
                  ->orWhere('sub_quarter', 'like', "%{$query}%");
            })
            ->with(['user:id,first_name,last_name', 'street'])
            ->limit(10)
            ->get()
            ->map(fn ($address) => $this->formatAddress($address));

        return $this->success($addresses);
    }

    protected function formatAddress(Address $address, bool $detailed = false): array
    {
        $data = [
            'id' => $address->id,
            'swAddress' => $address->sw_address,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'streetNumber' => $address->street_number,
            'streetName' => $address->street?->display_name,
            'lieuDit' => $address->lieu_dit,
            'quarter' => $address->quarter,
            'subQuarter' => $address->sub_quarter,
            'verificationStatus' => $address->verification_status,
            'createdAt' => $address->created_at->toIso8601String(),
            'owner' => [
                'id' => $address->user->id,
                'name' => $address->user->full_name,
            ],
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'houseType' => $address->house_type,
                'homeStatus' => $address->home_status,
                'description' => $address->description,
                'commune' => $address->street?->commune_name,
                'isNonHabitation' => $address->is_non_habitation,
                'nonHabitationType' => $address->non_habitation_type,
            ]);
        }

        return $data;
    }
}
