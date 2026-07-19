<?php

use Crater\Models\Invoice;

return [
    'features' => [
        'dashboard' => env('FEATURE_DASHBOARD', true),
        'customers' => env('FEATURE_CUSTOMERS', true),
        'items' => env('FEATURE_ITEMS', true),
        'estimates' => env('FEATURE_ESTIMATES', true),
        'invoices' => env('FEATURE_INVOICES', true),
        'electronic_invoicing' => env('FEATURE_ELECTRONIC_INVOICING', true),
        'accounting' => env('FEATURE_ACCOUNTING', true),
        'payments' => env('FEATURE_PAYMENTS', true),
        'users' => env('FEATURE_USERS', true),
        'settings' => env('FEATURE_SETTINGS', true),
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
        'roles' => env('FEATURE_ADVANCED_ROLES', false),
        'exchange_rate_provider' => env('FEATURE_EXCHANGE_RATES', false),
        'custom_fields' => env('FEATURE_CUSTOM_FIELDS', false),
        'expense_category' => env('FEATURE_EXPENSES', false),
        'update_app' => env('FEATURE_IN_APP_UPDATE', false),
        'mail_configuration' => env('FEATURE_MAIL_CONFIGURATION', false),
        'file_disk' => env('FEATURE_FILE_DISK', false),
        'backup' => env('FEATURE_BACKUP', false),
    ],

    'additional_main_menu' => [
        [
            'title' => 'Avoirs',
            'group' => 2,
            'link' => '/admin/credit-notes',
            'icon' => 'DocumentDuplicateIcon',
            'name' => 'Credit Notes',
            'owner_only' => false,
            'ability' => 'view-invoice',
            'model' => Invoice::class,
        ],
        [
            'title' => 'Facturation électronique',
            'group' => 2,
            'link' => '/admin/electronic-invoicing',
            'icon' => 'CloudUploadIcon',
            'name' => 'Electronic Invoicing',
            'owner_only' => false,
            'ability' => 'view-invoice',
            'model' => Invoice::class,
        ],
        [
            'title' => 'Comptabilité',
            'group' => 3,
            'link' => '/admin/accounting',
            'icon' => 'CalculatorIcon',
            'name' => 'Accounting',
            'owner_only' => false,
            'ability' => 'view-invoice',
            'model' => Invoice::class,
        ],
    ],

    'main_menu_map' => [
        'Dashboard' => 'dashboard',
        'Customers' => 'customers',
        'Items' => 'items',
        'Estimates' => 'estimates',
        'Invoices' => 'invoices',
        'Credit Notes' => 'invoices',
        'Electronic Invoicing' => 'electronic_invoicing',
        'Accounting' => 'accounting',
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

    'route_patterns' => [
        'invoices' => [
            'admin/invoices*',
            'admin/credit-notes*',
            'api/v1/invoices*',
            'api/v1/credit-notes*',
        ],
        'electronic_invoicing' => [
            'admin/electronic-invoicing*',
            'api/v1/electronic-invoicing*',
        ],
        'accounting' => [
            'admin/accounting*',
            'api/v1/accounting*',
        ],
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
        ],
        'expense_category' => [
            'admin/settings/expense-category*',
            'api/v1/categories*',
        ],
        'mail_configuration' => [
            'admin/settings/mail-configuration*',
            'api/v1/mail/*',
            'api/v1/company/mail/config*',
        ],
        'file_disk' => [
            'admin/settings/file-disk*',
            'api/v1/disks*',
            'api/v1/disk/drivers*',
        ],
        'backup' => [
            'admin/settings/backup*',
            'api/v1/backups*',
            'api/v1/download-backup*',
        ],
        'update_app' => [
            'admin/settings/update-app*',
            'api/v1/check/update*',
            'api/v1/update/*',
        ],
    ],

    // Les lectures GET restent disponibles pour les formulaires historiques.
    'settings_write_route_patterns' => [
        'roles' => [
            'api/v1/roles*',
        ],
        'custom_fields' => [
            'api/v1/custom-fields*',
        ],
    ],
];
