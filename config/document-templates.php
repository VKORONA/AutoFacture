<?php

return [
    'invoices' => [
        [
            'name' => 'invoice1',
            'label' => 'Premium AutoFacture',
            'description' => 'Design moderne avec identité forte, blocs émetteur/client et total très visible.',
            'theme' => 'premium',
            'preview' => 'img/document-templates/template-premium.svg',
        ],
        [
            'name' => 'invoice2',
            'label' => 'Classique Pro',
            'description' => 'Présentation administrative structurée, sobre et adaptée à l’impression.',
            'theme' => 'classic',
            'preview' => 'img/document-templates/template-classic.svg',
        ],
        [
            'name' => 'invoice3',
            'label' => 'Minimal élégant',
            'description' => 'Mise en page aérée, légère et immédiatement lisible.',
            'theme' => 'minimal',
            'preview' => 'img/document-templates/template-minimal.svg',
        ],
        [
            // La clé technique "nuit" est conservée pour ne pas casser les choix déjà enregistrés.
            'name' => 'nuit',
            'label' => 'Standard universel',
            'description' => 'Facture blanche, traditionnelle et compatible avec tous les usages papier.',
            'theme' => 'standard',
            'preview' => 'img/document-templates/template-standard.svg',
        ],
        [
            'name' => 'franchise-tva',
            'label' => 'Franchise TVA',
            'description' => 'Modèle clair avec mention TVA non applicable mise en évidence.',
            'theme' => 'franchise',
            'preview' => 'img/document-templates/template-franchise.svg',
        ],
    ],

    'estimates' => [
        [
            'name' => 'estimate1',
            'label' => 'Premium AutoFacture',
            'description' => 'Devis moderne avec objet de mission, parties clairement séparées et accord client.',
            'theme' => 'premium',
            'preview' => 'img/document-templates/template-premium.svg',
        ],
        [
            'name' => 'estimate2',
            'label' => 'Classique Pro',
            'description' => 'Proposition commerciale formelle, structurée et simple à imprimer.',
            'theme' => 'classic',
            'preview' => 'img/document-templates/template-classic.svg',
        ],
        [
            'name' => 'estimate3',
            'label' => 'Minimal élégant',
            'description' => 'Devis épuré avec davantage d’espace pour les descriptions détaillées.',
            'theme' => 'minimal',
            'preview' => 'img/document-templates/template-minimal.svg',
        ],
        [
            // La clé technique "nuit" est conservée pour ne pas casser les choix déjà enregistrés.
            'name' => 'nuit',
            'label' => 'Standard universel',
            'description' => 'Devis blanc, sobre et lisible, adapté à tous les métiers.',
            'theme' => 'standard',
            'preview' => 'img/document-templates/template-standard.svg',
        ],
        [
            'name' => 'franchise-tva',
            'label' => 'Franchise TVA',
            'description' => 'Devis intégrant clairement la mention de franchise en base de TVA.',
            'theme' => 'franchise',
            'preview' => 'img/document-templates/template-franchise.svg',
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
