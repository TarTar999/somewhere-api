<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OutageProgramme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EneoAdminController extends Controller
{
    private const ADMIN_EMAIL = 'fredy.rayan@gmail.com';
    private const DEFAULT_ENEO_URL = 'https://myeprdxpibv11.eneoapps.com/index.php/outage-programmes?region=littoral';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->user()->email !== self::ADMIN_EMAIL) {
                return response()->json([
                    'status' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Get ENEO configuration
     */
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'url' => config('services.eneo.url', self::DEFAULT_ENEO_URL),
                'default_url' => self::DEFAULT_ENEO_URL,
                'last_sync' => OutageProgramme::max('updated_at'),
                'total_programmes' => OutageProgramme::count(),
                'upcoming_programmes' => OutageProgramme::where('prog_date', '>=', now()->toDateString())->count(),
            ],
        ]);
    }

    /**
     * Update ENEO URL configuration
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        // Store in database or config
        // For now, we'll use a simple file-based approach
        $configPath = storage_path('app/eneo_config.json');
        $config = [
            'url' => $request->url,
            'updated_at' => now()->toIso8601String(),
            'updated_by' => $request->user()->email,
        ];

        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

        return response()->json([
            'status' => true,
            'message' => 'Configuration mise à jour',
            'data' => $config,
        ]);
    }

    /**
     * Sync programmes from ENEO API
     */
    public function sync(Request $request): JsonResponse
    {
        $configPath = storage_path('app/eneo_config.json');
        $baseUrl = self::DEFAULT_ENEO_URL;

        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $baseUrl = $config['url'] ?? self::DEFAULT_ENEO_URL;
        }

        $stats = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        try {
            Log::info('Starting ENEO manual sync', ['url' => $baseUrl, 'user' => $request->user()->email]);

            $page = 1;
            $hasMorePages = true;
            $allProgrammes = [];

            while ($hasMorePages && $page <= 50) {
                $separator = str_contains($baseUrl, '?') ? '&' : '?';
                $url = "{$baseUrl}{$separator}page={$page}&per_page=100";

                $response = Http::timeout(30)->accept('application/json')->get($url);

                if (!$response->successful()) {
                    Log::error('ENEO API request failed', ['status' => $response->status(), 'page' => $page]);
                    break;
                }

                $data = $response->json();

                if (!isset($data['status']) || $data['status'] !== true) {
                    Log::error('ENEO API returned error', ['response' => $data]);
                    break;
                }

                $programmes = $data['data']['programmes'] ?? [];
                $allProgrammes = array_merge($allProgrammes, $programmes);

                $pagination = $data['data']['pagination'] ?? [];
                $hasMorePages = $pagination['has_more_pages'] ?? false;
                $page++;

                usleep(100000); // 100ms delay
            }

            $stats['fetched'] = count($allProgrammes);
            $today = now()->toDateString();

            foreach ($allProgrammes as $prog) {
                // Only sync upcoming programmes
                if (($prog['prog_date'] ?? '') < $today) {
                    continue;
                }

                try {
                    // Check for duplicate (same location, date, time)
                    $existing = OutageProgramme::where('external_id', $prog['id'])->first();

                    if (!$existing) {
                        // Also check by location/date/time to avoid duplicates
                        $duplicate = OutageProgramme::where('prog_date', $prog['prog_date'])
                            ->where('prog_heure_debut', $prog['prog_heure_debut'] ?? null)
                            ->where('prog_heure_fin', $prog['prog_heure_fin'] ?? null)
                            ->where('ville', $prog['ville'] ?? null)
                            ->where('zone', $prog['zone'] ?? null)
                            ->first();

                        if ($duplicate) {
                            $stats['skipped']++;
                            continue;
                        }
                    }

                    OutageProgramme::createOrUpdateFromApi($prog);

                    if ($existing) {
                        $stats['updated']++;
                    } else {
                        $stats['created']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Failed to sync programme', [
                        'external_id' => $prog['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('ENEO manual sync completed', $stats);

            return response()->json([
                'status' => true,
                'message' => 'Synchronisation terminée',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('ENEO sync failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete past programmes
     */
    public function deletePast(): JsonResponse
    {
        $today = now()->toDateString();

        $deleted = OutageProgramme::where('prog_date', '<', $today)->delete();

        Log::info('Deleted past ENEO programmes', ['count' => $deleted]);

        return response()->json([
            'status' => true,
            'message' => "Programmes passés supprimés",
            'data' => [
                'deleted' => $deleted,
            ],
        ]);
    }

    /**
     * Get all programmes (for display)
     */
    public function getProgrammes(Request $request): JsonResponse
    {
        $query = OutageProgramme::query();

        if ($request->has('date')) {
            $query->where('prog_date', $request->date);
        }

        if ($request->has('ville')) {
            $query->where('ville', 'like', '%' . $request->ville . '%');
        }

        if ($request->boolean('upcoming_only', true)) {
            $query->where('prog_date', '>=', now()->toDateString());
        }

        $programmes = $query->orderBy('prog_date')
            ->orderBy('prog_heure_debut')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data' => $programmes,
        ]);
    }
}
