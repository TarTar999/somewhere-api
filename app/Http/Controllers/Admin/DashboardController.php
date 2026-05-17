<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Get statistics
        $stats = [
            'totalUsers' => User::count(),
            'newUsersThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
            'totalAddresses' => Address::count(),
            'pendingAddresses' => Address::pending()->count(),
            'approvedAddresses' => Address::verified()->count(),
            'totalCollections' => Collection::count(),
        ];

        // Recent activity
        $recentUsers = User::latest()
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at']);

        $pendingVerifications = Address::pending()
            ->with('user:id,first_name,last_name,email')
            ->latest()
            ->take(10)
            ->get();

        // Addresses by status
        $addressesByStatus = Address::select('verification_status', DB::raw('count(*) as count'))
            ->groupBy('verification_status')
            ->get()
            ->pluck('count', 'verification_status');

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'pendingVerifications' => $pendingVerifications,
            'addressesByStatus' => $addressesByStatus,
        ]);
    }
}
