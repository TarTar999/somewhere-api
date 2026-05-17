<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Collection::with('owner:id,first_name,last_name,email')
            ->withCount('addresses');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $collections = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Collection::count(),
            'system' => Collection::system()->count(),
            'custom' => Collection::custom()->count(),
            'delivery' => Collection::delivery()->count(),
        ];

        return Inertia::render('admin/collections/index', [
            'collections' => $collections,
            'filters' => $request->only(['search', 'type']),
            'stats' => $stats,
        ]);
    }

    public function show(Collection $collection): Response
    {
        $collection->load(['owner', 'addresses', 'sharedWith.sharedWithUser']);

        return Inertia::render('admin/collections/show', [
            'collection' => $collection,
        ]);
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->route('admin.collections.index')
            ->with('success', 'Collection deleted successfully');
    }
}
