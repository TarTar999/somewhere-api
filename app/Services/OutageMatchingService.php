<?php

namespace App\Services;

use App\Models\Address;
use App\Models\OutageProgramme;
use App\Models\OutageZone;
use Illuminate\Support\Collection;

class OutageMatchingService
{
    /**
     * Trouver les programmes de coupure pour une adresse
     */
    public function findOutagesForAddress(Address $address): Collection
    {
        $matchingProgrammes = collect();

        // Préparer les termes de recherche depuis l'adresse
        $searchTerms = $this->extractSearchTerms($address);

        if (empty($searchTerms)) {
            return $matchingProgrammes;
        }

        // Chercher dans les zones normalisées
        foreach ($searchTerms as $term) {
            $normalizedTerm = OutageZone::normalize($term);

            if (strlen($normalizedTerm) < 3) {
                continue;
            }

            $programmes = OutageProgramme::active()
                ->whereHas('zones', function ($query) use ($normalizedTerm) {
                    $query->where('zone_normalized', $normalizedTerm)
                          ->orWhere('zone_normalized', 'LIKE', "%{$normalizedTerm}%")
                          ->orWhereRaw("? LIKE CONCAT('%', zone_normalized, '%')", [$normalizedTerm]);
                })
                ->get();

            $matchingProgrammes = $matchingProgrammes->merge($programmes);
        }

        // Supprimer les doublons et trier par date
        return $matchingProgrammes
            ->unique('id')
            ->sortBy('prog_date')
            ->values();
    }

    /**
     * Extraire les termes de recherche d'une adresse
     */
    protected function extractSearchTerms(Address $address): array
    {
        $terms = [];

        // Lieu-dit (priorité haute)
        if (!empty($address->lieu_dit)) {
            $terms[] = $address->lieu_dit;
        }

        // Quartier
        if (!empty($address->quarter)) {
            $terms[] = $address->quarter;
        }

        // Sous-quartier
        if (!empty($address->sub_quarter)) {
            $terms[] = $address->sub_quarter;
        }

        // Display name (peut contenir des infos utiles)
        if (!empty($address->display_name)) {
            // Extraire les parties du display_name
            $parts = preg_split('/[\s,\-]+/', $address->display_name);
            foreach ($parts as $part) {
                $part = trim($part);
                if (strlen($part) >= 3) {
                    $terms[] = $part;
                }
            }
        }

        return array_unique(array_filter($terms));
    }

    /**
     * Formater les coupures pour l'API
     */
    public function formatOutagesForApi(Collection $outages): array
    {
        if ($outages->isEmpty()) {
            return [
                'hasOutages' => false,
                'outages' => [],
                'summary' => null,
            ];
        }

        $formatted = $outages->map(fn($o) => $o->toApiFormat())->values()->toArray();

        // Créer un résumé
        $today = $outages->filter(fn($o) => $o->isToday());
        $tomorrow = $outages->filter(fn($o) => $o->isTomorrow());
        $activeNow = $outages->filter(fn($o) => $o->isActiveNow());

        $summary = [
            'total' => $outages->count(),
            'todayCount' => $today->count(),
            'tomorrowCount' => $tomorrow->count(),
            'activeNowCount' => $activeNow->count(),
            'isActiveNow' => $activeNow->isNotEmpty(),
            'nextOutage' => $formatted[0] ?? null,
        ];

        // Message d'alerte
        if ($activeNow->isNotEmpty()) {
            $summary['alertLevel'] = 'critical';
            $summary['alertMessage'] = 'Coupure en cours dans votre zone';
        } elseif ($today->isNotEmpty()) {
            $summary['alertLevel'] = 'warning';
            $first = $today->first();
            $summary['alertMessage'] = "Coupure prévue aujourd'hui de {$first->prog_heure_debut->format('H:i')} à {$first->prog_heure_fin->format('H:i')}";
        } elseif ($tomorrow->isNotEmpty()) {
            $summary['alertLevel'] = 'info';
            $summary['alertMessage'] = "Coupure prévue demain";
        } else {
            $summary['alertLevel'] = 'info';
            $first = $outages->first();
            $summary['alertMessage'] = "Prochaine coupure le {$first->prog_date->format('d/m/Y')}";
        }

        return [
            'hasOutages' => true,
            'outages' => $formatted,
            'summary' => $summary,
        ];
    }

    /**
     * Enrichir une adresse avec les informations de coupure
     */
    public function enrichAddressWithOutages(array $addressData, Address $address): array
    {
        $outages = $this->findOutagesForAddress($address);
        $outageInfo = $this->formatOutagesForApi($outages);

        $addressData['powerOutages'] = $outageInfo;

        return $addressData;
    }

    /**
     * Enrichir plusieurs adresses avec les informations de coupure
     */
    public function enrichAddressesWithOutages(array $addressesData, Collection $addresses): array
    {
        // Créer un mapping id -> address pour accès rapide
        $addressMap = $addresses->keyBy('id');

        return array_map(function ($data) use ($addressMap) {
            $addressId = $data['id'] ?? null;
            if ($addressId && isset($addressMap[$addressId])) {
                return $this->enrichAddressWithOutages($data, $addressMap[$addressId]);
            }
            return $data;
        }, $addressesData);
    }
}
