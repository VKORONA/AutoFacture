<?php

return [
    'enabled' => env('FEATURE_MICRO_ENTREPRENEUR', true),

    /*
    |--------------------------------------------------------------------------
    | Barème local versionné
    |--------------------------------------------------------------------------
    |
    | AutoFacture conserve un barème local pour fonctionner sans connexion.
    | Il est daté et affiché comme une estimation. À terme, le calcul pourra
    | être recoupé avec le moteur officiel Mon-entreprise / Publicodes.
    |
    */
    'rate_tables' => [
        '2026-01-01' => [
            'goods_bic' => [
                'social' => 0.123,
                'income_tax' => 0.010,
            ],
            'service_bic' => [
                'social' => 0.212,
                'income_tax' => 0.017,
            ],
            'service_bnc' => [
                'social' => 0.256,
                'income_tax' => 0.022,
            ],
            'service_bnc_cipav' => [
                'social' => 0.232,
                'income_tax' => 0.022,
            ],
        ],
    ],

    'cfp_rates' => [
        'commercial' => 0.001,
        'artisan' => 0.003,
        'liberal' => 0.002,
    ],

    'default_settings' => [
        'declaration_frequency' => 'monthly',
        'cfp_profile' => 'commercial',
        'versement_liberatoire' => false,
        'acre_enabled' => false,
        'acre_rate_factor' => 0.5,
    ],

    'urssaf_third_party_api' => [
        'enabled' => env('URSSAF_MICRO_DECLARATION_ENABLED', false),
        'base_url' => env('URSSAF_MICRO_DECLARATION_BASE_URL'),
        'client_id' => env('URSSAF_MICRO_DECLARATION_CLIENT_ID'),
        'client_secret' => env('URSSAF_MICRO_DECLARATION_CLIENT_SECRET'),
    ],
];
