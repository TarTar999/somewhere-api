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
        'basic' => [
            'name' => 'Basic',
            'price' => 25000,
            'max_members' => 5,
            'documents_per_month' => 50,
            'features' => [
                'Jusqu\'à 5 membres',
                '50 documents par mois',
                'Gestion des adresses d\'équipe',
                'Support par email',
            ],
        ],
        'professional' => [
            'name' => 'Professional',
            'price' => 50000,
            'max_members' => 15,
            'documents_per_month' => 150,
            'features' => [
                'Jusqu\'à 15 membres',
                '150 documents par mois',
                'Support prioritaire',
                'Tableau de bord analytique',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 100000,
            'max_members' => 50,
            'documents_per_month' => 500,
            'features' => [
                'Jusqu\'à 50 membres',
                '500 documents par mois',
                'Support dédié',
                'Intégrations personnalisées',
                'Accès API',
            ],
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
