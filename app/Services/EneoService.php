<?php

namespace App\Services;

use App\Models\OutageProgramme;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EneoService
{
    protected string $baseUrl = 'https://myeprdxpibv11.eneoapps.com/index.php';
    protected string $region = 'littoral';

    /**
     * Récupérer tous les programmes de coupure depuis l'API ENEO
     */
    public function fetchOutageProgrammes(int $perPage = 100): array
    {
        $allProgrammes = [];
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $response = $this->fetchPage($page, $perPage);

            if (!$response['success']) {
                Log::error('Failed to fetch ENEO outage programmes', [
                    'page' => $page,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);
                break;
            }

            $programmes = $response['data']['programmes'] ?? [];
            $allProgrammes = array_merge($allProgrammes, $programmes);

            $pagination = $response['data']['pagination'] ?? [];
            $hasMorePages = $pagination['has_more_pages'] ?? false;
            $page++;

            // Sécurité: limiter à 50 pages max (5000 programmes)
            if ($page > 50) {
                Log::warning('ENEO fetch stopped at 50 pages limit');
                break;
            }

            // Petit délai pour ne pas surcharger l'API
            usleep(100000); // 100ms
        }

        return $allProgrammes;
    }

    /**
     * Récupérer une page de programmes
     */
    protected function fetchPage(int $page, int $perPage): array
    {
        try {
            $response = Http::timeout(30)
                ->accept('application/json')
                ->get("{$this->baseUrl}/outage-programmes", [
                    'region' => $this->region,
                    'page' => $page,
                    'per_page' => $perPage,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}",
                ];
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== true) {
                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Invalid response',
                ];
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('ENEO API request failed', [
                'page' => $page,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Récupérer uniquement les programmes futurs (aujourd'hui et après)
     */
    public function fetchUpcomingProgrammes(): array
    {
        $allProgrammes = $this->fetchOutageProgrammes();

        $today = now()->toDateString();

        return array_filter($allProgrammes, function ($programme) use ($today) {
            return ($programme['prog_date'] ?? '') >= $today;
        });
    }

    /**
     * Synchroniser les programmes avec la base de données
     */
    public function syncProgrammes(): array
    {
        $stats = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        Log::info('Starting ENEO outage programmes sync');

        $programmes = $this->fetchUpcomingProgrammes();
        $stats['fetched'] = count($programmes);

        foreach ($programmes as $programmeData) {
            try {
                $existing = OutageProgramme::where('external_id', $programmeData['id'])->first();

                OutageProgramme::createOrUpdateFromApi($programmeData);

                if ($existing) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Failed to sync outage programme', [
                    'external_id' => $programmeData['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Nettoyer les anciens programmes (plus de 7 jours passés)
        $deleted = OutageProgramme::where('prog_date', '<', now()->subDays(7)->toDateString())
            ->delete();

        $stats['deleted_old'] = $deleted;

        Log::info('ENEO outage programmes sync completed', $stats);

        return $stats;
    }

    /**
     * Récupérer les programmes pour une ville spécifique
     */
    public function getProgrammesForCity(string $city): \Illuminate\Database\Eloquent\Collection
    {
        return OutageProgramme::active()
            ->inCity($city)
            ->orderBy('prog_date')
            ->orderBy('prog_heure_debut')
            ->get();
    }

    /**
     * Récupérer les programmes pour une date spécifique
     */
    public function getProgrammesForDate(string $date): \Illuminate\Database\Eloquent\Collection
    {
        return OutageProgramme::where('prog_date', $date)
            ->orderBy('prog_heure_debut')
            ->get();
    }
}
