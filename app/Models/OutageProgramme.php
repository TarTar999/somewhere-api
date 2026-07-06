<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutageProgramme extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'region',
        'ville',
        'lib_traveaux',
        'zone',
        'quartier',
        'prog_date',
        'prog_heure_debut',
        'prog_heure_fin',
        'duree_minutes',
        'statut',
        'zones_array',
        'travaux_normalises',
        'category',
        'priority',
        'metadata',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'prog_date' => 'date',
            'prog_heure_debut' => 'datetime:H:i',
            'prog_heure_fin' => 'datetime:H:i',
            'duree_minutes' => 'integer',
            'zones_array' => 'array',
            'metadata' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Relation avec les zones normalisées
     */
    public function zones(): HasMany
    {
        return $this->hasMany(OutageZone::class);
    }

    /**
     * Scope pour les coupures actives (aujourd'hui et futures)
     */
    public function scopeActive($query)
    {
        return $query->where('prog_date', '>=', now()->toDateString());
    }

    /**
     * Scope pour les coupures d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->where('prog_date', now()->toDateString());
    }

    /**
     * Scope pour les coupures de demain
     */
    public function scopeTomorrow($query)
    {
        return $query->where('prog_date', now()->addDay()->toDateString());
    }

    /**
     * Scope pour les coupures dans les X prochains jours
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->whereBetween('prog_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ]);
    }

    /**
     * Scope par ville
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('ville', 'LIKE', "%{$city}%");
    }

    /**
     * Scope par région
     */
    public function scopeInRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Vérifie si la coupure est active maintenant
     */
    public function isActiveNow(): bool
    {
        if (!$this->prog_date->isToday()) {
            return false;
        }

        $now = now();
        $start = $this->prog_date->copy()->setTimeFromTimeString($this->prog_heure_debut->format('H:i:s'));
        $end = $this->prog_date->copy()->setTimeFromTimeString($this->prog_heure_fin->format('H:i:s'));

        return $now->between($start, $end);
    }

    /**
     * Vérifie si la coupure est aujourd'hui
     */
    public function isToday(): bool
    {
        return $this->prog_date->isToday();
    }

    /**
     * Vérifie si la coupure est demain
     */
    public function isTomorrow(): bool
    {
        return $this->prog_date->isTomorrow();
    }

    /**
     * Formater pour l'API
     */
    public function toApiFormat(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->external_id,
            'region' => $this->region,
            'ville' => $this->ville,
            'travaux' => $this->travaux_normalises ?? $this->lib_traveaux,
            'travauxDescription' => $this->lib_traveaux,
            'zones' => $this->zones_array,
            'date' => $this->prog_date->toDateString(),
            'dateFormatted' => $this->prog_date->format('d/m/Y'),
            'heureDebut' => $this->prog_heure_debut->format('H:i'),
            'heureFin' => $this->prog_heure_fin->format('H:i'),
            'horaireComplet' => $this->prog_heure_debut->format('H:i') . ' - ' . $this->prog_heure_fin->format('H:i'),
            'dureeMinutes' => $this->duree_minutes,
            'dureeFormatee' => $this->formatDuration(),
            'statut' => $this->statut,
            'category' => $this->category,
            'priority' => $this->priority,
            'isActiveNow' => $this->isActiveNow(),
            'isToday' => $this->isToday(),
            'isTomorrow' => $this->isTomorrow(),
            'daysFromNow' => $this->prog_date->diffInDays(now(), false),
        ];
    }

    /**
     * Formater la durée
     */
    protected function formatDuration(): string
    {
        $hours = intdiv($this->duree_minutes, 60);
        $minutes = $this->duree_minutes % 60;

        if ($minutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h{$minutes}min";
    }

    /**
     * Créer ou mettre à jour depuis les données de l'API ENEO
     */
    public static function createOrUpdateFromApi(array $data): self
    {
        $zonesArray = $data['zones_array'] ?? [];

        // Nettoyer les zones (trim)
        $zonesArray = array_map('trim', $zonesArray);
        $zonesArray = array_filter($zonesArray);

        $programme = self::updateOrCreate(
            ['external_id' => $data['id']],
            [
                'region' => $data['region'] ?? '',
                'ville' => $data['ville'] ?? '',
                'lib_traveaux' => $data['lib_traveaux'] ?? '',
                'zone' => $data['zone'] ?? '',
                'quartier' => $data['quartier'] ?? '',
                'prog_date' => $data['prog_date'],
                'prog_heure_debut' => $data['prog_heure_debut'],
                'prog_heure_fin' => $data['prog_heure_fin'],
                'duree_minutes' => $data['duree_minutes'] ?? 0,
                'statut' => $data['statut'] ?? 'prevu',
                'zones_array' => $zonesArray,
                'travaux_normalises' => $data['travaux_normalises'] ?? null,
                'category' => $data['work_info']['category'] ?? null,
                'priority' => $data['work_info']['priority'] ?? 'moyenne',
                'metadata' => [
                    'schedule_info' => $data['schedule_info'] ?? null,
                    'location_info' => $data['location_info'] ?? null,
                    'work_info' => $data['work_info'] ?? null,
                ],
                'fetched_at' => now(),
            ]
        );

        // Mettre à jour les zones normalisées
        $programme->syncNormalizedZones($zonesArray);

        return $programme;
    }

    /**
     * Synchroniser les zones normalisées
     */
    public function syncNormalizedZones(array $zones): void
    {
        // Supprimer les anciennes zones
        $this->zones()->delete();

        // Créer les nouvelles
        foreach ($zones as $zone) {
            $zone = trim($zone);
            if (empty($zone)) {
                continue;
            }

            $this->zones()->create([
                'zone_name' => $zone,
                'zone_normalized' => OutageZone::normalize($zone),
            ]);
        }
    }
}
