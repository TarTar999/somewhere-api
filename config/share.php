<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Share Mode
    |--------------------------------------------------------------------------
    |
    | This determines how shared links redirect to the app.
    | 'development' - Uses Expo URL for development builds
    | 'production' - Uses deep links for production app
    |
    */
    'mode' => env('APP_SHARE_MODE', 'development'),

    /*
    |--------------------------------------------------------------------------
    | Deep Link Configuration
    |--------------------------------------------------------------------------
    |
    | The URL scheme used by the production app for deep linking.
    |
    */
    'deep_link_scheme' => env('APP_DEEP_LINK_SCHEME', 'somewhereapp'),

    /*
    |--------------------------------------------------------------------------
    | Expo Development URL
    |--------------------------------------------------------------------------
    |
    | The Expo development server URL for testing deep links in development.
    |
    */
    'expo_url' => env('APP_EXPO_URL', 'exp://localhost:8081'),

    /*
    |--------------------------------------------------------------------------
    | Share Base URL
    |--------------------------------------------------------------------------
    |
    | The public URL used for generating share links.
    |
    */
    'base_url' => env('APP_SHARE_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | App Store URLs
    |--------------------------------------------------------------------------
    |
    | Fallback URLs to app stores when the app is not installed.
    |
    */
    'stores' => [
        'ios' => env('APP_STORE_IOS_URL', ''),
        'android' => env('APP_STORE_ANDROID_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo URL
    |--------------------------------------------------------------------------
    |
    | URL to the app logo for Open Graph meta tags.
    |
    */
    'logo_url' => env('APP_LOGO_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | App Information
    |--------------------------------------------------------------------------
    |
    | Information used in meta tags for social sharing.
    |
    */
    'app_name' => env('APP_NAME', 'SomeWhere'),
    'app_description' => 'Votre adresse unique au Cameroun - Localisez, partagez et naviguez facilement.',
];
