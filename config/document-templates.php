<?php

return [
    'invoices' => [
        [
            'name' => 'invoice1',
            'label' => 'Premium AutoFacture',
            'description' => 'Design moderne, détaillé et professionnel.',
            'theme' => 'premium',
            'preview' => 'invoice1.png',
        ],
        [
            'name' => 'invoice2',
            'label' => 'Classique Pro',
            'description' => 'Structure claire adaptée aux dossiers administratifs.',
            'theme' => 'classic',
            'preview' => 'invoice2.png',
        ],
        [
            'name' => 'invoice3',
            'label' => 'Minimal élégant',
            'description' => 'Présentation épurée avec une lecture immédiate.',
            'theme' => 'minimal',
            'preview' => 'invoice3.png',
        ],
        [
            // La clé technique "nuit" est conservée pour ne pas casser les choix déjà enregistrés.
            'name' => 'nuit',
            'label' => 'Standard universel',
            'description' => 'Facture blanche, légère et habituelle, optimisée pour l’impression papier.',
            'theme' => 'standard',
            'preview' => null,
        ],
        [
            'name' => 'franchise-tva',
            'label' => 'Franchise TVA',
            'description' => 'Mention TVA non applicable mise en avant.',
            'theme' => 'franchise',
            'preview' => null,
        ],
    ],

    'estimates' => [
        [
            'name' => 'estimate1',
            'label' => 'Premium AutoFacture',
            'description' => 'Design moderne, détaillé et professionnel.',
            'theme' => 'premium',
            'preview' => 'estimate1.png',
        ],
        [
            'name' => 'estimate2',
            'label' => 'Classique Pro',
            'description' => 'Structure claire adaptée aux propositions commerciales.',
            'theme' => 'classic',
            'preview' => 'estimate2.png',
        ],
        [
            'name' => 'estimate3',
            'label' => 'Minimal élégant',
            'description' => 'Présentation épurée avec une lecture immédiate.',
            'theme' => 'minimal',
            'preview' => 'estimate3.png',
        ],
        [
            // La clé technique "nuit" est conservée pour ne pas casser les choix déjà enregistrés.
            'name' => 'nuit',
            'label' => 'Standard universel',
            'description' => 'Devis blanc, sobre et lisible, adapté à tous les métiers et à l’impression.',
            'theme' => 'standard',
            'preview' => null,
        ],
        [
            'name' => 'franchise-tva',
            'label' => 'Franchise TVA',
            'description' => 'Mention TVA non applicable intégrée au devis.',
            'theme' => 'franchise',
            'preview' => null,
        ],
    ],

    'credit_notes' => [
        'premium',
        'classique',
        'minimal',
        'nuit',
        'franchise-tva',
    ],
];
