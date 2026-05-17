<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Address::with('user:id,first_name,last_name,email');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sw_address', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('quarter', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('verification_status', $status);
        }

        // Filter by house type
        if ($houseType = $request->get('house_type')) {
            $query->where('house_type', $houseType);
        }

        $addresses = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Address::count(),
            'pending' => Address::pending()->count(),
            'approved' => Address::verified()->count(),
            'rejected' => Address::where('verification_status', 'rejected')->count(),
        ];

        return Inertia::render('admin/addresses/index', [
            'addresses' => $addresses,
            'filters' => $request->only(['search', 'status', 'house_type']),
            'stats' => $stats,
        ]);
    }

    public function show(Address $address): Response
    {
        $address->load(['user', 'collections']);

        return Inertia::render('admin/addresses/show', [
            'address' => $address,
        ]);
    }

    public function verify(Address $address): RedirectResponse
    {
        $address->update(['verification_status' => 'approved']);

        return redirect()->back()
            ->with('success', 'Address verified successfully');
    }

    public function reject(Request $request, Address $address): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $address->update(['verification_status' => 'rejected']);

        // TODO: Send notification to user with rejection reason

        return redirect()->back()
            ->with('success', 'Address rejected');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $address->delete();

        return redirect()->route('admin.addresses.index')
            ->with('success', 'Address deleted successfully');
    }
}
