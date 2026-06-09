<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define the available subscription plans for companies.
    | Prices are in XAF (Central African CFA franc).
    |
    */
    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'price' => env('PLAN_STARTER_PRICE', 15000),
            'max_members' => env('PLAN_STARTER_MAX_MEMBERS', 3),
            'documents_per_month' => env('PLAN_STARTER_DOCS_PER_MONTH', 25),
            'max_zones' => env('PLAN_STARTER_MAX_ZONES', 5),
            'max_pois' => env('PLAN_STARTER_MAX_POIS', 25),
            'max_campaigns' => env('PLAN_STARTER_MAX_CAMPAIGNS', 1),
            'max_alerts' => env('PLAN_STARTER_MAX_ALERTS', 5),
            'api_access' => false,
            'api_calls' => 0,
            'features' => [
                'Jusqu\'à 3 membres',
                '25 documents par mois',
                '5 zones géographiques',
                '25 points d\'intérêt',
                'Support par email',
            ],
        ],
        'basic' => [
            'name' => 'Basic',
            'price' => env('PLAN_BASIC_PRICE', 25000),
            'max_members' => env('PLAN_BASIC_MAX_MEMBERS', 5),
            'documents_per_month' => env('PLAN_BASIC_DOCS_PER_MONTH', 50),
            'max_zones' => env('PLAN_BASIC_MAX_ZONES', 15),
            'max_pois' => env('PLAN_BASIC_MAX_POIS', 100),
            'max_campaigns' => env('PLAN_BASIC_MAX_CAMPAIGNS', 3),
            'max_alerts' => env('PLAN_BASIC_MAX_ALERTS', 10),
            'api_access' => false,
            'api_calls' => 0,
            'features' => [
                'Jusqu\'à 5 membres',
                '50 documents par mois',
                '15 zones géographiques',
                '100 points d\'intérêt',
                '3 campagnes actives',
                'Support par email',
            ],
        ],
        'professional' => [
            'name' => 'Professional',
            'price' => env('PLAN_PROFESSIONAL_PRICE', 50000),
            'max_members' => env('PLAN_PROFESSIONAL_MAX_MEMBERS', 15),
            'documents_per_month' => env('PLAN_PROFESSIONAL_DOCS_PER_MONTH', 150),
            'max_zones' => env('PLAN_PROFESSIONAL_MAX_ZONES', 50),
            'max_pois' => env('PLAN_PROFESSIONAL_MAX_POIS', 500),
            'max_campaigns' => env('PLAN_PROFESSIONAL_MAX_CAMPAIGNS', 10),
            'max_alerts' => env('PLAN_PROFESSIONAL_MAX_ALERTS', 50),
            'api_access' => env('PLAN_PROFESSIONAL_API_ACCESS', true),
            'api_calls' => env('PLAN_PROFESSIONAL_API_CALLS', 10000),
            'features' => [
                'Jusqu\'à 15 membres',
                '150 documents par mois',
                '50 zones géographiques',
                '500 points d\'intérêt',
                '10 campagnes actives',
                'Alertes géolocalisées',
                'Accès API (10K appels/mois)',
                'Support prioritaire',
                'Tableau de bord analytique',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => env('PLAN_ENTERPRISE_PRICE', 100000),
            'max_members' => env('PLAN_ENTERPRISE_MAX_MEMBERS', 50),
            'documents_per_month' => env('PLAN_ENTERPRISE_DOCS_PER_MONTH', 500),
            'max_zones' => env('PLAN_ENTERPRISE_MAX_ZONES', 200),
            'max_pois' => env('PLAN_ENTERPRISE_MAX_POIS', 2000),
            'max_campaigns' => env('PLAN_ENTERPRISE_MAX_CAMPAIGNS', 0), // 0 = illimité
            'max_alerts' => env('PLAN_ENTERPRISE_MAX_ALERTS', 0), // 0 = illimité
            'api_access' => env('PLAN_ENTERPRISE_API_ACCESS', true),
            'api_calls' => env('PLAN_ENTERPRISE_API_CALLS', 100000),
            'features' => [
                'Jusqu\'à 50 membres',
                '500 documents par mois',
                '200 zones géographiques',
                '2000 points d\'intérêt',
                'Campagnes illimitées',
                'Alertes illimitées',
                'Module livraisons',
                'Accès API (100K appels/mois)',
                'Support dédié',
                'Intégrations personnalisées',
                'Analytique avancée',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-ons
    |--------------------------------------------------------------------------
    |
    | Additional modules that can be purchased separately.
    |
    */
    'addons' => [
        'extra_zones_10' => [
            'name' => 'Pack 10 zones supplémentaires',
            'price' => env('ADDON_EXTRA_ZONES_PACK_10', 5000),
        ],
        'extra_pois_50' => [
            'name' => 'Pack 50 POIs supplémentaires',
            'price' => env('ADDON_EXTRA_POIS_PACK_50', 3000),
        ],
        'delivery_module' => [
            'name' => 'Module Livraisons',
            'price' => env('ADDON_DELIVERY_MODULE', 15000),
        ],
        'campaign_module' => [
            'name' => 'Module Campagnes Avancé',
            'price' => env('ADDON_CAMPAIGN_MODULE', 20000),
        ],
        'analytics_advanced' => [
            'name' => 'Analytique Avancée',
            'price' => env('ADDON_ANALYTICS_ADVANCED', 10000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Period
    |--------------------------------------------------------------------------
    |
    | Number of trial days for new company subscriptions.
    |
    */
    'trial_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after subscription expiry before suspending access.
    |
    */
    'grace_period_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | Invitation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for member invitations.
    |
    */
    'invitation' => [
        'expires_in_days' => 7,
    ],
];
