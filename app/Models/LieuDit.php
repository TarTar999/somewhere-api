<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LieuDit extends Model
{
    use HasFactory;

    protected $table = 'lieux_dits';

    protected $fillable = [
        'name',
        'name_normalized',
        'city',
        'region',
        'is_verified',
        'is_system',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_system' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Normalize a string for search (remove accents, lowercase)
     */
    public static function normalize(string $text): string
    {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Remove accents
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u', 'e', $text);
        $text = preg_replace('/[ìíîï]/u', 'i', $text);
        $text = preg_replace('/[òóôõö]/u', 'o', $text);
        $text = preg_replace('/[ùúûü]/u', 'u', $text);
        $text = preg_replace('/[ýÿ]/u', 'y', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[ñ]/u', 'n', $text);

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Boot method to auto-normalize name
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lieuDit) {
            if (empty($lieuDit->name_normalized)) {
                $lieuDit->name_normalized = self::normalize($lieuDit->name);
            }
        });

        static::updating(function ($lieuDit) {
            if ($lieuDit->isDirty('name')) {
                $lieuDit->name_normalized = self::normalize($lieuDit->name);
            }
        });
    }

    /**
     * Search lieux-dits by query
     */
    public static function search(string $query, ?string $city = null, int $limit = 20)
    {
        $normalized = self::normalize($query);

        $builder = self::query()
            ->where('name_normalized', 'LIKE', "{$normalized}%")
            ->orWhere('name_normalized', 'LIKE', "%{$normalized}%");

        if ($city) {
            $builder->where('city', $city);
        }

        return $builder
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Find or create a lieu-dit
     */
    public static function findOrCreateByName(string $name, ?string $city = null): self
    {
        $normalized = self::normalize($name);

        $lieuDit = self::where('name_normalized', $normalized)
            ->when($city, fn($q) => $q->where('city', $city))
            ->first();

        if (!$lieuDit) {
            $lieuDit = self::create([
                'name' => $name,
                'name_normalized' => $normalized,
                'city' => $city,
                'is_verified' => false,
                'is_system' => false,
                'usage_count' => 1,
            ]);
        }

        return $lieuDit;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
