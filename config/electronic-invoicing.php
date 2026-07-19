<?php

return [
    'default_provider' => env('EINVOICING_DEFAULT_PROVIDER', 'superpdp'),

    'providers' => [
        'superpdp' => [
            'name' => 'SUPER PDP',
            'portal_url' => env('SUPERPDP_PORTAL_URL', 'https://www.superpdp.tech'),
            'documentation_url' => env('SUPERPDP_DOCUMENTATION_URL', 'https://www.superpdp.tech/documentation/'),
            'environment' => env('SUPERPDP_ENVIRONMENT', 'sandbox'),
            'test_url' => env('SUPERPDP_TEST_URL'),
            'client_id_header' => env('SUPERPDP_CLIENT_ID_HEADER'),
            'client_secret_header' => env('SUPERPDP_CLIENT_SECRET_HEADER'),
            'timeout' => env('SUPERPDP_TIMEOUT', 10),
        ],
    ],
];
