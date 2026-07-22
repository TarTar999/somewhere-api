<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutageProgramme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EneoController extends Controller
{
    private const ALLOWED_EMAIL = 'fredy.rayan@gmail.com';
    private const DEFAULT_ENEO_URL = 'https://myeprdxpibv11.eneoapps.com/index.php/outage-programmes?region=littoral';

    private function checkAccess(Request $request): void
    {
        if ($request->user()->email !== self::ALLOWED_EMAIL) {
            abort(403, 'Accès non autorisé à cette section.');
        }
    }

    /**
     * Show ENEO admin page
     */
    public function index(Request $request): Response
    {
        $this->checkAccess($request);
        $configPath = storage_path('app/eneo_config.json');
        $config = [
            'url' => self::DEFAULT_ENEO_URL,
            'updated_at' => null,
        ];

        if (file_exists($configPath)) {
            $config = array_merge($config, json_decode(file_get_contents($configPath), true) ?? []);
        }

        $stats = [
            'total' => OutageProgramme::count(),
            'upcoming' => OutageProgramme::where('prog_date', '>=', now()->toDateString())->count(),
            'today' => OutageProgramme::where('prog_date', now()->toDateString())->count(),
            'past' => OutageProgramme::where('prog_date', '<', now()->toDateString())->count(),
            'lastSync' => OutageProgramme::max('updated_at'),
        ];

        $programmes = OutageProgramme::where('prog_date', '>=', now()->toDateString())
            ->orderBy('prog_date')
            ->orderBy('prog_heure_debut')
            ->limit(50)
            ->get();

        return Inertia::render('admin/eneo/index', [
            'config' => $config,
            'stats' => $stats,
            'programmes' => $programmes,
            'defaultUrl' => self::DEFAULT_ENEO_URL,
        ]);
    }

    /**
     * Update ENEO configuration
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $this->checkAccess($request);

        $request->validate([
            'url' => 'required|url',
        ]);

        $configPath = storage_path('app/eneo_config.json');
        $config = [
            'url' => $request->url,
            'updated_at' => now()->toIso8601String(),
            'updated_by' => $request->user()->email,
        ];

        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

        return response()->json([
            'success' => true,
            'message' => 'Configuration mise à jour',
            'config' => $config,
        ]);
    }

    /**
     * Sync programmes from ENEO API
     */
    public function sync(Request $request): JsonResponse
    {
        $this->checkAccess($request);

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
            Log::info('Starting ENEO manual sync from web', ['url' => $baseUrl, 'user' => $request->user()->email]);

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
                    // Check for existing by external_id
                    $existing = OutageProgramme::where('external_id', $prog['id'])->first();

                    if (!$existing) {
                        // Check for duplicate by location/date/time
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
                'success' => true,
                'message' => 'Synchronisation terminée',
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('ENEO sync failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete past programmes
     */
    public function deletePast(Request $request): JsonResponse
    {
        $this->checkAccess($request);

        $today = now()->toDateString();
        $deleted = OutageProgramme::where('prog_date', '<', $today)->delete();

        Log::info('Deleted past ENEO programmes from web', ['count' => $deleted]);

        return response()->json([
            'success' => true,
            'message' => "{$deleted} programmes passés supprimés",
            'deleted' => $deleted,
        ]);
    }
}
