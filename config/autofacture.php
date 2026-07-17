<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fonctions AutoFacture
    |--------------------------------------------------------------------------
    |
    | Les indicateurs pilotent à la fois la visibilité des menus et l'accès
    | direct aux anciennes URL de Crater. Une fonction désactivée reste dans
    | le code et la base, mais n'est plus utilisable dans le MVP.
    |
    */
    'features' => [
        'dashboard' => env('FEATURE_DASHBOARD', true),
        'customers' => env('FEATURE_CUSTOMERS', true),
        'items' => env('FEATURE_ITEMS', true),
        'estimates' => env('FEATURE_ESTIMATES', true),
        'invoices' => env('FEATURE_INVOICES', true),
        'payments' => env('FEATURE_PAYMENTS', true),
        'users' => env('FEATURE_USERS', true),
        'settings' => env('FEATURE_SETTINGS', true),

        // Fonctions volontairement masquées dans le MVP initial.
        'recurring_invoices' => env('FEATURE_RECURRING_INVOICES', false),
        'expenses' => env('FEATURE_EXPENSES', false),
        'reports' => env('FEATURE_REPORTS', false),
        'modules' => env('FEATURE_MODULES', false),
    ],

    'settings_features' => [
        'account_settings' => true,
        'company_information' => true,
        'preferences' => true,
        'customization' => true,
        'notifications' => true,
        'tax_types' => true,
        'payment_modes' => true,
        'notes' => true,
        'mail_configuration' => true,
        'file_disk' => true,
        'backup' => true,

        // Masquées pour alléger l'expérience du MVP.
        'roles' => env('FEATURE_ADVANCED_ROLES', false),
        'exchange_rate_provider' => env('FEATURE_EXCHANGE_RATES', false),
        'custom_fields' => env('FEATURE_CUSTOM_FIELDS', false),
        'expense_category' => env('FEATURE_EXPENSES', false),
        'update_app' => env('FEATURE_IN_APP_UPDATE', false),
    ],

    /*
    | Les noms correspondent aux valeurs `name` de config/crater.php.
    */
    'main_menu_map' => [
        'Dashboard' => 'dashboard',
        'Customers' => 'customers',
        'Items' => 'items',
        'Estimates' => 'estimates',
        'Invoices' => 'invoices',
        'Recurring Invoices' => 'recurring_invoices',
        'Payments' => 'payments',
        'Expenses' => 'expenses',
        'Modules' => 'modules',
        'Users' => 'users',
        'Reports' => 'reports',
        'Settings' => 'settings',
    ],

    'setting_menu_map' => [
        'Account Settings' => 'account_settings',
        'Company information' => 'company_information',
        'Preferences' => 'preferences',
        'Customization' => 'customization',
        'Roles' => 'roles',
        'Exchange Rate Provider' => 'exchange_rate_provider',
        'Notifications' => 'notifications',
        'Tax types' => 'tax_types',
        'Payment modes' => 'payment_modes',
        'Custom fields' => 'custom_fields',
        'Notes' => 'notes',
        'Expense Category' => 'expense_category',
        'Mail Configuration' => 'mail_configuration',
        'File Disk' => 'file_disk',
        'Backup' => 'backup',
        'Update App' => 'update_app',
    ],

    /*
    | Motifs Laravel Request::is() bloqués lorsque la fonction est désactivée.
    */
    'route_patterns' => [
        'recurring_invoices' => [
            'admin/recurring-invoices*',
            'api/v1/recurring-invoice-frequency*',
            'api/v1/recurring-invoices*',
        ],
        'expenses' => [
            'admin/expenses*',
            'api/v1/expenses*',
            'api/v1/categories*',
            'api/v1/*/customer/expenses*',
        ],
        'reports' => [
            'admin/reports*',
            'api/v1/reports*',
        ],
        'modules' => [
            'admin/modules*',
            'api/v1/modules*',
        ],
    ],

    'settings_route_patterns' => [
        'roles' => [
            'admin/settings/roles-settings*',
            'api/v1/roles*',
            'api/v1/abilities*',
        ],
        'exchange_rate_provider' => [
            'admin/settings/exchange-rate-provider*',
            'api/v1/exchange-rate-providers*',
            'api/v1/used-currencies*',
            'api/v1/supported-currencies*',
            'api/v1/currencies/*/exchange-rate*',
            'api/v1/currencies/*/active-provider*',
            'api/v1/currencies/bulk-update-exchange-rate*',
        ],
        'custom_fields' => [
            'admin/settings/custom-fields*',
            'api/v1/custom-fields*',
        ],
        'expense_category' => [
            'admin/settings/expense-category*',
            'api/v1/categories*',
        ],
        'update_app' => [
            'admin/settings/update-app*',
            'api/v1/check/update*',
            'api/v1/update/*',
        ],
    ],
];
