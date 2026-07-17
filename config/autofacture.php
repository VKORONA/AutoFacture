<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Visibilité des fonctions dans l'interface
    |--------------------------------------------------------------------------
    |
    | Ces indicateurs masquent uniquement les entrées de menu. Les routes et
    | les données restent intactes afin d'éviter une suppression irréversible
    | pendant la construction du MVP.
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
];
