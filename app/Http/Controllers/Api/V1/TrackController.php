<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Track;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * List all tracks for the authenticated user (owned + shared)
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        // Get user's own tracks
        $ownTracks = $user->tracks()->latest()->get();

        // Get tracks shared with user
        $sharedTracks = $user->sharedTracks()->latest()->get();

        return $this->success([
            'owned' => $this->formatTracks($ownTracks),
            'shared' => $this->formatTracks($sharedTracks, true),
        ]);
    }

    /**
     * Create a new track
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'structure' => ['required', 'array', 'min:2'],
            'structure.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'structure.*.lon' => ['required', 'numeric', 'between:-180,180'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'isPublic' => ['nullable', 'boolean'],
        ]);

        $track = Track::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'structure' => $request->structure,
            'color' => $request->color ?? '#3B82F6',
            'is_public' => $request->isPublic ?? false,
        ]);

        return $this->success($this->formatTrack($track), 'Track created successfully', 201);
    }

    /**
     * Show a specific track
     */
    public function show(Track $track): JsonResponse
    {
        if (!$track->canBeViewedBy(auth()->user())) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success($this->formatTrack($track));
    }

    /**
     * Show a track by share token (public access)
     */
    public function showByToken(string $token): JsonResponse
    {
        $track = Track::where('share_token', $token)->first();

        if (!$track) {
            return $this->error('Track not found', 404);
        }

        return $this->success($this->formatTrack($track));
    }

    /**
     * Update a track
     */
    public function update(Request $request, Track $track): JsonResponse
    {
        if (!$track->canBeEditedBy(auth()->user())) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'structure' => ['sometimes', 'array', 'min:2'],
            'structure.*.lat' => ['required_with:structure', 'numeric', 'between:-90,90'],
            'structure.*.lon' => ['required_with:structure', 'numeric', 'between:-180,180'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'isPublic' => ['nullable', 'boolean'],
        ]);

        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->name;
        }
        if ($request->has('description')) {
            $data['description'] = $request->description;
        }
        if ($request->has('structure')) {
            $data['structure'] = $request->structure;
        }
        if ($request->has('color')) {
            $data['color'] = $request->color;
        }
        if ($request->has('isPublic')) {
            $data['is_public'] = $request->isPublic;
        }

        $track->update($data);

        return $this->success($this->formatTrack($track), 'Track updated successfully');
    }

    /**
     * Delete a track
     */
    public function destroy(Track $track): JsonResponse
    {
        if ($track->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $track->delete();

        return $this->noContent();
    }

    /**
     * Share a track with another user
     */
    public function share(Request $request, Track $track): JsonResponse
    {
        if ($track->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'email' => ['required_without:userId', 'email', 'exists:users,email'],
            'userId' => ['required_without:email', 'integer', 'exists:users,id'],
            'permission' => ['nullable', 'in:view,edit'],
        ]);

        $targetUser = $request->userId
            ? User::find($request->userId)
            : User::where('email', $request->email)->first();

        if (!$targetUser) {
            return $this->error('User not found', 404);
        }

        if ($targetUser->id === auth()->id()) {
            return $this->error('Cannot share with yourself', 400);
        }

        // Sync the share (update if exists, create if not)
        $track->sharedWith()->syncWithoutDetaching([
            $targetUser->id => ['permission' => $request->permission ?? 'view'],
        ]);

        return $this->success(null, 'Track shared successfully');
    }

    /**
     * Remove share from a user
     */
    public function unshare(Request $request, Track $track): JsonResponse
    {
        if ($track->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $track->sharedWith()->detach($request->userId);

        return $this->success(null, 'Share removed successfully');
    }

    /**
     * Regenerate share token
     */
    public function regenerateToken(Track $track): JsonResponse
    {
        if ($track->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $track->update([
            'share_token' => \Illuminate\Support\Str::random(32),
        ]);

        return $this->success([
            'shareToken' => $track->share_token,
            'shareUrl' => url("/tracks/shared/{$track->share_token}"),
        ], 'Share token regenerated');
    }

    protected function formatTrack(Track $track, bool $isShared = false): array
    {
        $data = [
            'id' => $track->id,
            'name' => $track->name,
            'description' => $track->description,
            'structure' => $track->structure,
            'color' => $track->color,
            'distance' => $track->distance,
            'pointsCount' => $track->points_count,
            'isPublic' => $track->is_public,
            'shareToken' => $track->share_token,
            'shareUrl' => url("/tracks/shared/{$track->share_token}"),
            'createdAt' => $track->created_at->toIso8601String(),
            'updatedAt' => $track->updated_at->toIso8601String(),
        ];

        if ($isShared) {
            $data['permission'] = $track->pivot->permission ?? 'view';
            $data['owner'] = [
                'id' => $track->user->id,
                'name' => $track->user->full_name,
            ];
        }

        return $data;
    }

    protected function formatTracks($tracks, bool $isShared = false): array
    {
        return $tracks->map(fn($t) => $this->formatTrack($t, $isShared))->toArray();
    }
}
