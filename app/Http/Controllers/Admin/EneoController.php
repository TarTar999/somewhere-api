<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutageProgramme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Save programmes sent from client-side fetch
     */
    public function sync(Request $request): JsonResponse
    {
        $this->checkAccess($request);

        $request->validate([
            'programmes' => 'required|array',
        ]);

        $programmes = $request->input('programmes', []);

        $stats = [
            'fetched' => count($programmes),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $today = now()->toDateString();

        Log::info('Saving ENEO programmes from client', ['count' => count($programmes), 'user' => $request->user()->email]);

        foreach ($programmes as $prog) {
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

        Log::info('ENEO programmes saved', $stats);

        return response()->json([
            'success' => true,
            'message' => 'Synchronisation terminée',
            'stats' => $stats,
        ]);
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
