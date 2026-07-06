<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'smsvas' => [
        'user' => env('SMSVAS_USER'),
        'password' => env('SMSVAS_PASSWORD'),
        'sender_id' => env('SMSVAS_SENDER_ID', 'Somewhere'),
    ],

    'fapshi' => [
        'api_user' => env('FAPSHI_API_USER'),
        'api_key' => env('FAPSHI_API_KEY'),
        'webhook_secret' => env('FAPSHI_WEBHOOK_SECRET'),
        'proof_of_location_price' => env('FAPSHI_PROOF_OF_LOCATION_PRICE', 1000), // Price in XAF
    ],

    'mapbox' => [
        'token' => env('MAPBOX_ACCESS_TOKEN', ''),
        'style' => env('MAPBOX_STYLE', 'default'),
        'custom_style_url' => env('MAPBOX_CUSTOM_STYLE_URL', ''),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_id_ios' => env('GOOGLE_CLIENT_ID_IOS'),
        'client_id_android' => env('GOOGLE_CLIENT_ID_ANDROID'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'bundle_id' => env('APPLE_BUNDLE_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH', storage_path('app/apple-auth-key.p8')),
    ],

];
