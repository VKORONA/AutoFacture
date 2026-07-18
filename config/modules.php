<?php

use Nwidart\Modules\Activators\FileActivator;

return [
    'namespace' => 'Modules',

    /*
     * AutoFacture conserve la lecture des anciens modules Crater, mais désactive
     * leur générateur et leurs commandes d'administration dans le SaaS.
     */
    'commands' => [],

    'scan' => [
        'enabled' => false,
        'paths' => [],
    ],

    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
            'cache-key' => 'autofacture.modules.activator.installed',
            'cache-lifetime' => 604800,
        ],
    ],

    'activator' => 'file',
];
