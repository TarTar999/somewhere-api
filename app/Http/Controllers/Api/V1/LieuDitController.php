<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LieuDit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LieuDitController extends Controller
{
    /**
     * Search lieux-dits with autocomplete
     * GET /api/lieux-dits/search?q=bona&city=Douala&limit=15
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'city' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $query = trim($request->q);
        $city = $request->city;
        $limit = $request->limit ?? 20;

        $lieuxDits = LieuDit::search($query, $city, $limit);

        $results = $lieuxDits->map(fn($ld) => [
            'id' => $ld->id,
            'name' => $ld->name,
            'city' => $ld->city,
            'region' => $ld->region,
            'isVerified' => $ld->is_verified,
            'usageCount' => $ld->usage_count,
        ]);

        return $this->success([
            'query' => $query,
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Get popular lieux-dits (most used)
     * GET /api/lieux-dits/popular?city=Douala&limit=20
     */
    public function popular(Request $request): JsonResponse
    {
        $city = $request->city;
        $limit = $request->limit ?? 20;

        $lieuxDits = LieuDit::query()
            ->when($city, fn($q) => $q->where('city', $city))
            ->where('usage_count', '>', 0)
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();

        $results = $lieuxDits->map(fn($ld) => [
            'id' => $ld->id,
            'name' => $ld->name,
            'city' => $ld->city,
            'region' => $ld->region,
            'isVerified' => $ld->is_verified,
            'usageCount' => $ld->usage_count,
        ]);

        return $this->success([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * List all lieux-dits (paginated)
     * GET /api/lieux-dits?city=Douala&perPage=50
     */
    public function index(Request $request): JsonResponse
    {
        $city = $request->city;
        $perPage = $request->perPage ?? 50;

        $query = LieuDit::query()
            ->when($city, fn($q) => $q->where('city', $city))
            ->orderBy('name');

        $lieuxDits = $query->paginate($perPage);

        $lieuxDits->getCollection()->transform(fn($ld) => [
            'id' => $ld->id,
            'name' => $ld->name,
            'city' => $ld->city,
            'region' => $ld->region,
            'isVerified' => $ld->is_verified,
            'usageCount' => $ld->usage_count,
        ]);

        return $this->paginated($lieuxDits, 'Lieux-dits retrieved');
    }

    /**
     * Get available cities
     * GET /api/lieux-dits/cities
     */
    public function cities(): JsonResponse
    {
        $cities = LieuDit::select('city')
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return $this->success([
            'cities' => $cities,
            'count' => $cities->count(),
        ]);
    }
}
