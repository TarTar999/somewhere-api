<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Validity
    |--------------------------------------------------------------------------
    |
    | Number of months a document remains valid.
    |
    */
    'validity_months' => env('DOCUMENT_VALIDITY_MONTHS', 3),

    /*
    |--------------------------------------------------------------------------
    | Document Prices (in XAF - FCFA)
    |--------------------------------------------------------------------------
    |
    | Prices for each document type. All prices are in FCFA.
    |
    */
    'prices' => [
        'location_plan' => env('PRICE_LOCATION_PLAN', 0),
        'proof_of_residence' => env('PRICE_PROOF_OF_RESIDENCE', 3000),
        'address_verification' => env('PRICE_ADDRESS_VERIFICATION', 1500),
        'bulk_export' => env('PRICE_BULK_EXPORT', 5000),
        'express_processing' => env('PRICE_EXPRESS_PROCESSING', 1000), // Additional fee for same-day
    ],

    /*
    |--------------------------------------------------------------------------
    | Company Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Subscription plans for companies with pricing and features.
    |
    */
    'subscriptions' => [
        'starter' => [
            'name' => 'Starter',
            'description' => 'Idéal pour les petites entreprises',
            'monthly_price' => env('PRICE_COMPANY_STARTER_MONTHLY', 15000),
            'yearly_price' => env('PRICE_COMPANY_STARTER_YEARLY', 150000),
            'features' => [
                'max_members' => 5,
                'documents_per_month' => 50,
                'zones_limit' => 10,
                'api_access' => false,
                'priority_support' => false,
                'custom_branding' => false,
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'description' => 'Pour les entreprises en croissance',
            'monthly_price' => env('PRICE_COMPANY_PRO_MONTHLY', 35000),
            'yearly_price' => env('PRICE_COMPANY_PRO_YEARLY', 350000),
            'features' => [
                'max_members' => 20,
                'documents_per_month' => 200,
                'zones_limit' => 50,
                'api_access' => true,
                'priority_support' => true,
                'custom_branding' => false,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'description' => 'Pour les grandes organisations',
            'monthly_price' => env('PRICE_COMPANY_ENTERPRISE_MONTHLY', 75000),
            'yearly_price' => env('PRICE_COMPANY_ENTERPRISE_YEARLY', 750000),
            'features' => [
                'max_members' => -1, // Unlimited
                'documents_per_month' => -1, // Unlimited
                'zones_limit' => -1, // Unlimited
                'api_access' => true,
                'priority_support' => true,
                'custom_branding' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Company details for invoices and receipts.
    |
    */
    'company' => [
        'name' => env('COMPANY_NAME', 'Ket-Up Sarl'),
        'brand' => env('COMPANY_BRAND', 'SomeWhere App'),
        'address' => env('COMPANY_ADDRESS', 'Douala, Cameroun'),
        'phone' => env('COMPANY_PHONE', '+237 600 000 000'),
        'email' => env('COMPANY_EMAIL', 'contact@somewhere-app.com'),
        'website' => env('COMPANY_WEBSITE', 'www.somewhere-app.com'),
        'support_email' => env('COMPANY_SUPPORT_EMAIL', 'support@somewhere-app.com'),
        'rccm' => env('COMPANY_RCCM', ''),
        'niu' => env('COMPANY_NIU', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Number Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes used for generating document numbers.
    |
    */
    'prefixes' => [
        'location_plan' => 'SW-LOC',
        'proof_of_residence' => 'SW-RES',
        'invoice' => 'SW-INV',
        'receipt' => 'SW-REC',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    |
    | Settings for PDF generation.
    |
    */
    'pdf' => [
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'logo_path' => env('DOCUMENT_LOGO_PATH', 'images/logo-somewhere.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermark Settings
    |--------------------------------------------------------------------------
    |
    | Settings for PDF watermarking to prevent unauthorized redistribution.
    |
    */
    'watermark' => [
        'enabled' => env('PDF_WATERMARK_ENABLED', true),
        'opacity' => env('PDF_WATERMARK_OPACITY', 0.1),
        'angle' => env('PDF_WATERMARK_ANGLE', -45),
        'font_size' => env('PDF_WATERMARK_FONT_SIZE', 48),
        'color' => env('PDF_WATERMARK_COLOR', '#000000'),
        'include_user_id' => env('PDF_WATERMARK_INCLUDE_USER_ID', true),
        'include_date' => env('PDF_WATERMARK_INCLUDE_DATE', true),
        'include_document_number' => env('PDF_WATERMARK_INCLUDE_DOC_NUMBER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notification Settings
    |--------------------------------------------------------------------------
    |
    | Settings for email notifications related to documents.
    |
    */
    'notifications' => [
        'expiration_warning_days' => env('DOCUMENT_EXPIRATION_WARNING_DAYS', 7),
        'send_download_notification' => env('DOCUMENT_SEND_DOWNLOAD_NOTIFICATION', true),
        'send_verification_notification' => env('DOCUMENT_SEND_VERIFICATION_NOTIFICATION', true),
    ],
];
