<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Street extends Model
{
    use HasFactory;

    protected $fillable = [
        'osm_id',
        'osm_type',
        'display_name',
        'code',
        'commune_name',
        'commune_number',
        'structure',
        'bounding_box',
        'start_lat',
        'start_lon',
    ];

    protected function casts(): array
    {
        return [
            'osm_id' => 'integer',
            'commune_number' => 'integer',
            'structure' => 'array',
            'bounding_box' => 'array',
            'start_lat' => 'decimal:8',
            'start_lon' => 'decimal:8',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Generate street code based on commune number and street rank
     */
    public function generateCode(): string
    {
        // Get the rank of this street in the database
        $rank = static::where('commune_number', $this->commune_number)
            ->where('id', '<=', $this->id)
            ->count();

        // Convert rank to hexadecimal (uppercase)
        $hexRank = strtoupper(dechex($rank));

        return "{$this->commune_number}.{$hexRank}";
    }

    /**
     * Get the starting point coordinates
     */
    public function getStartPointAttribute(): ?array
    {
        if (!$this->structure || empty($this->structure['coordinates'])) {
            return null;
        }

        $firstCoord = $this->structure['coordinates'][0];
        return [
            'lon' => $firstCoord[0],
            'lat' => $firstCoord[1],
        ];
    }

    /**
     * Get the ending point coordinates
     */
    public function getEndPointAttribute(): ?array
    {
        if (!$this->structure || empty($this->structure['coordinates'])) {
            return null;
        }

        $lastCoord = end($this->structure['coordinates']);
        return [
            'lon' => $lastCoord[0],
            'lat' => $lastCoord[1],
        ];
    }

    /**
     * Find street by OSM ID or create from Nominatim data
     */
    public static function findOrCreateFromNominatim(array $nominatimData): self
    {
        $osmId = $nominatimData['osm_id'];

        $street = static::where('osm_id', $osmId)->first();

        if ($street) {
            return $street;
        }

        // Extract commune number from city name (e.g., "Douala III" -> 3)
        $communeName = $nominatimData['address']['city'] ?? $nominatimData['address']['town'] ?? null;
        $communeNumber = self::extractCommuneNumber($communeName);

        $street = static::create([
            'osm_id' => $osmId,
            'osm_type' => $nominatimData['osm_type'] ?? 'way',
            'display_name' => $nominatimData['display_name'],
            'commune_name' => $communeName,
            'commune_number' => $communeNumber,
            'structure' => $nominatimData['geojson'] ?? null,
            'bounding_box' => $nominatimData['boundingbox'] ?? null,
            'start_lat' => $nominatimData['geojson']['coordinates'][0][1] ?? null,
            'start_lon' => $nominatimData['geojson']['coordinates'][0][0] ?? null,
        ]);

        // Generate and save the code
        $street->code = $street->generateCode();
        $street->save();

        return $street;
    }

    /**
     * Extract commune number from name (e.g., "Douala III" -> 3, "Douala 2" -> 2)
     */
    protected static function extractCommuneNumber(?string $communeName): int
    {
        if (!$communeName) {
            return 1;
        }

        // Match Roman numerals (I, II, III, IV, V, VI, VII, VIII, IX, X)
        $romanNumerals = [
            'X' => 10, 'IX' => 9, 'VIII' => 8, 'VII' => 7, 'VI' => 6,
            'V' => 5, 'IV' => 4, 'III' => 3, 'II' => 2, 'I' => 1,
        ];

        foreach ($romanNumerals as $roman => $value) {
            if (preg_match('/\b' . $roman . '\b/i', $communeName)) {
                return $value;
            }
        }

        // Match Arabic numerals
        if (preg_match('/(\d+)/', $communeName, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }
}
