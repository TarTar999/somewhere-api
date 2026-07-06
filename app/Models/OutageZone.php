<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutageZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'outage_programme_id',
        'zone_name',
        'zone_normalized',
    ];

    /**
     * Relation avec le programme de coupure
     */
    public function outageProgramme(): BelongsTo
    {
        return $this->belongsTo(OutageProgramme::class);
    }

    /**
     * Normaliser un nom de zone pour le matching
     */
    public static function normalize(string $text): string
    {
        // Convertir en minuscules
        $text = mb_strtolower($text, 'UTF-8');

        // Supprimer les accents
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u', 'e', $text);
        $text = preg_replace('/[ìíîï]/u', 'i', $text);
        $text = preg_replace('/[òóôõö]/u', 'o', $text);
        $text = preg_replace('/[ùúûü]/u', 'u', $text);
        $text = preg_replace('/[ýÿ]/u', 'y', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[ñ]/u', 'n', $text);

        // Supprimer les caractères spéciaux sauf espaces
        $text = preg_replace('/[^a-z0-9\s]/u', '', $text);

        // Supprimer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Trouver les programmes de coupure correspondant à un lieu-dit ou quartier
     */
    public static function findMatchingProgrammes(string $lieuDit, ?string $quartier = null, ?string $ville = null): \Illuminate\Database\Eloquent\Collection
    {
        $normalizedLieuDit = self::normalize($lieuDit);
        $normalizedQuartier = $quartier ? self::normalize($quartier) : null;

        $query = self::query()
            ->whereHas('outageProgramme', function ($q) {
                $q->where('prog_date', '>=', now()->toDateString());
            })
            ->where(function ($q) use ($normalizedLieuDit, $normalizedQuartier) {
                // Match exact ou partiel sur le lieu-dit
                $q->where('zone_normalized', $normalizedLieuDit)
                  ->orWhere('zone_normalized', 'LIKE', "%{$normalizedLieuDit}%")
                  ->orWhere(function ($sq) use ($normalizedLieuDit) {
                      // Le lieu-dit contient la zone
                      $sq->whereRaw("? LIKE CONCAT('%', zone_normalized, '%')", [$normalizedLieuDit]);
                  });

                // Aussi chercher dans le quartier
                if ($normalizedQuartier) {
                    $q->orWhere('zone_normalized', $normalizedQuartier)
                      ->orWhere('zone_normalized', 'LIKE', "%{$normalizedQuartier}%");
                }
            });

        // Filtrer par ville si fournie
        if ($ville) {
            $query->whereHas('outageProgramme', function ($q) use ($ville) {
                $q->where('ville', 'LIKE', "%{$ville}%");
            });
        }

        return $query->with('outageProgramme')
            ->get()
            ->pluck('outageProgramme')
            ->unique('id')
            ->sortBy('prog_date');
    }
}
