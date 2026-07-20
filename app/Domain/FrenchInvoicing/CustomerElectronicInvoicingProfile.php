<?php

namespace Crater\Domain\FrenchInvoicing;

use Crater\Models\Customer;

final class CustomerElectronicInvoicingProfile
{
    public function for(Customer $customer): array
    {
        if ($customer->customer_type === 'individual') {
            return [
                'customer_category' => 'b2c',
                'document_mode' => 'standard_pdf',
                'delivery_channel' => 'direct_customer_delivery',
                'requires_approved_platform' => false,
                'e_invoicing_applicable' => false,
                'e_reporting_applicable' => true,
                'status' => 'not_applicable_b2c',
                'label' => 'Pas de facturation électronique B2B nécessaire',
                'message' => 'Le client est un particulier. Une facture PDF standard peut être remise au client. La transaction reste identifiable pour le e-reporting lorsque celui-ci s’applique à l’entreprise.',
            ];
        }

        $hasBusinessIdentifier = filled($customer->siret) || filled($customer->vat_number);

        return [
            'customer_category' => 'b2b',
            'document_mode' => 'factur_x_ready',
            'delivery_channel' => 'approved_platform',
            'requires_approved_platform' => true,
            'e_invoicing_applicable' => true,
            'e_reporting_applicable' => false,
            'status' => $hasBusinessIdentifier ? 'ready' : 'incomplete',
            'label' => $hasBusinessIdentifier
                ? 'Client professionnel prêt pour la facturation électronique'
                : 'Informations professionnelles à compléter',
            'message' => $hasBusinessIdentifier
                ? 'Le client est identifié comme professionnel. Les données structurées pourront être préparées pour Factur-X et la plateforme agréée.'
                : 'Ajoutez au minimum le SIRET ou le numéro de TVA du client avant un envoi électronique B2B.',
        ];
    }
}
