<?php

use Crater\Domain\FrenchInvoicing\FrenchLegalMentionBuilder;
use Crater\Models\Company;

it('génère les mentions légales de base', function () {
    $company = new Company([
        'legal_form' => 'SASU',
        'siren' => '732829320',
        'siret' => '73282932000074',
        'vat_number' => 'FR12732829320',
        'ape_code' => '6201Z',
        'rcs_city' => 'Toulouse',
        'share_capital' => 1000,
        'vat_regime' => 'standard',
    ]);

    $mentions = app(FrenchLegalMentionBuilder::class)->forCompany($company);

    expect($mentions)->toContain(
        'SASU',
        'SIREN 732829320',
        'SIRET 73282932000074',
        'RCS Toulouse',
        'Code APE 6201Z',
        'TVA intracommunautaire FR12732829320',
        'Capital social 1 000,00 €'
    );
});

it('ajoute la mention de franchise en base de TVA', function () {
    $company = new Company([
        'vat_regime' => 'franchise_base',
    ]);

    $mentions = app(FrenchLegalMentionBuilder::class)->forCompany($company);

    expect($mentions)->toContain('TVA non applicable, art. 293 B du CGI');
});

it('sépare les coordonnées bancaires des mentions publiques', function () {
    $company = new Company([
        'iban' => 'FR7630006000011234567890189',
        'bic' => 'AGRIFRPP',
    ]);

    expect(app(FrenchLegalMentionBuilder::class)->paymentDetails($company))->toBe([
        'iban' => 'FR7630006000011234567890189',
        'bic' => 'AGRIFRPP',
    ]);
});
