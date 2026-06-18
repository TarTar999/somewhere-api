<?php

namespace App\Services;

use App\Models\DocumentDownload;
use App\Models\ProofOfLocation;
use GeoIp2\Database\Reader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocumentDownloadService
{
    /**
     * Track a document download.
     */
    public function trackDownload(
        Model $document,
        Request $request,
        string $downloadType = 'download',
        bool $isWatermarked = false
    ): DocumentDownload {
        $download = DocumentDownload::create([
            'documentable_type' => get_class($document),
            'documentable_id' => $document->getKey(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'browser' => $this->detectBrowser($request->userAgent()),
            'os' => $this->detectOs($request->userAgent()),
            'geo_data' => $this->getGeoData($request->ip()),
            'referrer' => $request->header('referer'),
            'download_type' => $downloadType,
            'is_watermarked' => $isWatermarked,
        ]);

        // Update document's download count if applicable
        if (method_exists($document, 'incrementDownloadCount')) {
            $document->incrementDownloadCount();
        } elseif (property_exists($document, 'download_count')) {
            $document->increment('download_count');
            $document->update(['last_downloaded_at' => now()]);
        }

        return $download;
    }

    /**
     * Get download statistics for a document.
     */
    public function getDocumentStats(Model $document): array
    {
        $downloads = DocumentDownload::query()
            ->where('documentable_type', get_class($document))
            ->where('documentable_id', $document->getKey());

        return [
            'total_downloads' => $downloads->count(),
            'unique_users' => $downloads->whereNotNull('user_id')->distinct('user_id')->count(),
            'unique_ips' => $downloads->whereNotNull('ip_address')->distinct('ip_address')->count(),
            'by_device' => $downloads->clone()
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->pluck('count', 'device_type')
                ->toArray(),
            'by_country' => $this->getDownloadsByCountry($downloads->clone()),
            'recent_downloads' => $downloads->clone()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($d) => [
                    'date' => $d->created_at->toIso8601String(),
                    'device' => $d->device_type,
                    'location' => $d->location,
                    'type' => $d->download_type,
                ]),
        ];
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent.
     */
    private function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $browsers = [
            'Edge' => '/edge|edg/i',
            'Chrome' => '/chrome/i',
            'Safari' => '/safari/i',
            'Firefox' => '/firefox/i',
            'Opera' => '/opera|opr/i',
            'IE' => '/msie|trident/i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Other';
    }

    /**
     * Detect OS from user agent.
     */
    private function detectOs(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $systems = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os x/i',
            'Linux' => '/linux/i',
            'Android' => '/android/i',
            'iOS' => '/iphone|ipad|ipod/i',
        ];

        foreach ($systems as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Other';
    }

    /**
     * Get geolocation data from IP address.
     */
    private function getGeoData(?string $ip): ?array
    {
        if (!$ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return ['country' => 'Local', 'city' => 'Development'];
        }

        try {
            // Try to use MaxMind GeoIP2 if available
            $dbPath = storage_path('app/geoip/GeoLite2-City.mmdb');

            if (file_exists($dbPath)) {
                $reader = new Reader($dbPath);
                $record = $reader->city($ip);

                return [
                    'country' => $record->country->name,
                    'country_code' => $record->country->isoCode,
                    'city' => $record->city->name,
                    'latitude' => $record->location->latitude,
                    'longitude' => $record->location->longitude,
                ];
            }

            // Fallback: Use ip-api.com (free, no API key needed, limited)
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,lat,lon");

            if ($response) {
                $data = json_decode($response, true);

                if (($data['status'] ?? '') === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('GeoIP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get downloads grouped by country.
     */
    private function getDownloadsByCountry($query): array
    {
        return $query
            ->whereNotNull('geo_data')
            ->get()
            ->groupBy(fn($d) => $d->geo_data['country'] ?? 'Unknown')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    /**
     * Generate watermark text for a download.
     */
    public function generateWatermarkText(Model $document, Request $request): string
    {
        $userId = auth()->id();
        $timestamp = now()->format('Y-m-d H:i:s');
        $ip = $this->maskIp($request->ip());

        $documentNumber = '';
        if (method_exists($document, 'getDocumentNumber')) {
            $documentNumber = $document->getDocumentNumber();
        } elseif (property_exists($document, 'document_number')) {
            $documentNumber = $document->document_number;
        }

        return sprintf(
            'Téléchargé le %s | ID: %s | Doc: %s',
            $timestamp,
            $userId ? "U{$userId}" : $ip,
            $documentNumber
        );
    }

    /**
     * Mask IP address for privacy in watermark.
     */
    private function maskIp(?string $ip): string
    {
        if (!$ip) {
            return 'Unknown';
        }

        // Mask last octet for IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.xxx', $ip);
        }

        // Mask last segment for IPv6
        return preg_replace('/:[^:]+$/', ':xxxx', $ip);
    }
}
