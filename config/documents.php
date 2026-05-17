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
    | Document Prices (in XAF)
    |--------------------------------------------------------------------------
    |
    | Prices for each document type.
    |
    */
    'prices' => [
        'location_plan' => env('FAPSHI_LOCATION_PLAN_PRICE', env('PRICE_LOCATION_PLAN', 2000)),
        'proof_of_residence' => env('FAPSHI_PROOF_OF_LOCATION_PRICE', env('PRICE_PROOF_OF_RESIDENCE', 3000)),
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
];
