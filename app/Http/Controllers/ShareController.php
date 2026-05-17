<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShareController extends Controller
{
    /**
     * Handle address share link
     * Renders a page with Open Graph meta tags and redirects to the app
     */
    public function address(string $id): View
    {
        $address = Address::with('street')->find($id);

        if (!$address) {
            return view('share.not-found', [
                'type' => 'address',
                'config' => $this->getShareConfig(),
            ]);
        }

        $shareData = $this->buildAddressShareData($address);

        return view('share.address', [
            'address' => $address,
            'shareData' => $shareData,
            'config' => $this->getShareConfig(),
            'deepLink' => $this->buildDeepLink('address', $id),
        ]);
    }

    /**
     * Handle address share link by SW address
     */
    public function addressBySw(string $swAddress): View
    {
        $address = Address::with('street')
            ->where('sw_address', urldecode($swAddress))
            ->first();

        if (!$address) {
            return view('share.not-found', [
                'type' => 'address',
                'config' => $this->getShareConfig(),
            ]);
        }

        $shareData = $this->buildAddressShareData($address);

        return view('share.address', [
            'address' => $address,
            'shareData' => $shareData,
            'config' => $this->getShareConfig(),
            'deepLink' => $this->buildDeepLink('address', $address->id),
        ]);
    }

    /**
     * Build share data for an address
     */
    protected function buildAddressShareData(Address $address): array
    {
        $title = $address->sw_address ?? 'Adresse SomeWhere';
        $description = $this->buildAddressDescription($address);

        return [
            'title' => $title . ' - SomeWhere',
            'description' => $description,
            'image' => $this->getMapPreviewUrl($address),
            'url' => $this->getShareUrl('address', $address->id),
            'type' => 'website',
            'siteName' => config('share.app_name', 'SomeWhere'),
            // Twitter specific
            'twitterCard' => 'summary_large_image',
            'twitterTitle' => $title,
            'twitterDescription' => $description,
        ];
    }

    /**
     * Build description for address
     */
    protected function buildAddressDescription(Address $address): string
    {
        $parts = [];

        if ($address->quarter) {
            $parts[] = $address->quarter;
        }

        if ($address->sub_quarter) {
            $parts[] = $address->sub_quarter;
        }

        if ($address->street && $address->street->display_name) {
            $parts[] = $address->street->display_name;
        }

        if (empty($parts)) {
            return config('share.app_description');
        }

        return implode(', ', $parts) . ' - Localisez cette adresse avec SomeWhere';
    }

    /**
     * Get map preview URL for Open Graph image
     */
    protected function getMapPreviewUrl(Address $address): string
    {
        // Use a static map image or the app logo
        // You can integrate with Google Static Maps API or Mapbox here
        // For now, return the app logo
        $logoUrl = config('share.logo_url');

        if ($logoUrl) {
            return $logoUrl;
        }

        // Generate a static map URL (example with OpenStreetMap)
        // In production, you might want to use Google Static Maps or Mapbox
        return config('app.url') . '/images/logo-share.png';
    }

    /**
     * Build the deep link URL based on environment
     */
    protected function buildDeepLink(string $type, string $id): array
    {
        $mode = config('share.mode', 'development');
        $path = "{$type}/{$id}";

        if ($mode === 'development') {
            // Expo development URL
            $expoUrl = config('share.expo_url', 'exp://localhost:8081');
            return [
                'primary' => "{$expoUrl}/--/{$path}",
                'fallback' => null,
                'mode' => 'development',
            ];
        }

        // Production deep link
        $scheme = config('share.deep_link_scheme', 'somewhereapp');
        $stores = config('share.stores', []);

        return [
            'primary' => "{$scheme}://{$path}",
            'fallback' => [
                'ios' => $stores['ios'] ?? null,
                'android' => $stores['android'] ?? null,
                'web' => config('app.url'),
            ],
            'mode' => 'production',
        ];
    }

    /**
     * Get the public share URL
     */
    protected function getShareUrl(string $type, string $id): string
    {
        $baseUrl = config('share.base_url', config('app.url'));
        return "{$baseUrl}/share/{$type}/{$id}";
    }

    /**
     * Get share configuration for views
     */
    protected function getShareConfig(): array
    {
        return [
            'mode' => config('share.mode', 'development'),
            'appName' => config('share.app_name', 'SomeWhere'),
            'appDescription' => config('share.app_description'),
            'logoUrl' => config('share.logo_url'),
            'stores' => config('share.stores', []),
        ];
    }
}
