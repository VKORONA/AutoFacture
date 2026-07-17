<?php

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;

it('prépare des paramètres français cohérents', function () {
    $settings = app(FrenchCompanyDefaults::class)->settings(3);

    expect($settings)
        ->toMatchArray([
            'currency' => 3,
            'time_zone' => 'Europe/Paris',
            'language' => 'fr',
            'carbon_date_format' => 'd/m/Y',
            'moment_date_format' => 'DD/MM/YYYY',
            'invoice_number_format' => '{{SERIES:FAC}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'estimate_number_format' => '{{SERIES:DEV}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'payment_number_format' => '{{SERIES:REG}}{{DELIMITER:-}}{{SEQUENCE:6}}',
            'retrospective_edits' => 'disable_on_invoice_sent',
        ])
        ->and($settings['invoice_company_address_format'])->toContain('{COMPANY_LEGAL_MENTIONS}')
        ->and($settings['invoice_billing_address_format'])->toContain('{CUSTOMER_LEGAL_MENTIONS}');
});
