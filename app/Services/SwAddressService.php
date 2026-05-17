<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Str;

class SwAddressService
{
    private const PREFIX = 'SW';
    private const SEPARATOR = '-';

    public function generate(float $latitude, float $longitude): string
    {
        // Generate a unique SW address based on coordinates
        $geoCode = $this->encodeCoordinates($latitude, $longitude);
        $uniqueId = strtoupper(Str::random(4));

        $swAddress = self::PREFIX . self::SEPARATOR . $geoCode . self::SEPARATOR . $uniqueId;

        // Ensure uniqueness
        $attempts = 0;
        while (Address::where('sw_address', $swAddress)->exists() && $attempts < 10) {
            $uniqueId = strtoupper(Str::random(4));
            $swAddress = self::PREFIX . self::SEPARATOR . $geoCode . self::SEPARATOR . $uniqueId;
            $attempts++;
        }

        return $swAddress;
    }

    private function encodeCoordinates(float $latitude, float $longitude): string
    {
        // Convert coordinates to a short alphanumeric code
        // Using a simplified encoding for readability
        $latCode = $this->encodeNumber($latitude, 90);
        $lonCode = $this->encodeNumber($longitude, 180);

        return strtoupper(substr($latCode . $lonCode, 0, 8));
    }

    private function encodeNumber(float $number, float $max): string
    {
        // Normalize to positive range
        $normalized = ($number + $max) / (2 * $max);
        // Convert to base36 representation
        $encoded = base_convert((int) ($normalized * 1679615), 10, 36); // 36^4 - 1

        return str_pad($encoded, 4, '0', STR_PAD_LEFT);
    }

    public function parse(string $swAddress): ?array
    {
        if (!Str::startsWith($swAddress, self::PREFIX . self::SEPARATOR)) {
            return null;
        }

        $parts = explode(self::SEPARATOR, $swAddress);
        if (count($parts) !== 3) {
            return null;
        }

        return [
            'prefix' => $parts[0],
            'geo_code' => $parts[1],
            'unique_id' => $parts[2],
        ];
    }

    public function isValid(string $swAddress): bool
    {
        return $this->parse($swAddress) !== null;
    }
}
