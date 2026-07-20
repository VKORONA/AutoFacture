<?php

use Crater\Domain\Payments\SepaQrCodeService;
use Crater\Models\Company;

it('builds a valid EPC SEPA payload from company banking details', function () {
    $company = new Company([
        'name' => 'BATI-MESURE',
        'iban' => 'FR76 3000 4008 2800 0100 1234 567',
        'bic' => 'BNPAFRPPXXX',
    ]);

    $payload = app(SepaQrCodeService::class)->buildPayload(
        $company,
        176000,
        'FA-2026-00548',
    );

    expect($payload)->not->toBeNull();

    $lines = explode("\n", $payload);

    expect($lines)
        ->toHaveCount(12)
        ->and($lines[0])->toBe('BCD')
        ->and($lines[1])->toBe('002')
        ->and($lines[3])->toBe('SCT')
        ->and($lines[4])->toBe('BNPAFRPPXXX')
        ->and($lines[5])->toBe('BATI-MESURE')
        ->and($lines[6])->toBe('FR7630004008280001001234567')
        ->and($lines[7])->toBe('EUR1760.00')
        ->and($lines[10])->toBe('AutoFacture FA-2026-00548');
});

it('does not create a payment code without an IBAN', function () {
    $company = new Company([
        'name' => 'Entreprise sans IBAN',
        'iban' => null,
    ]);

    expect(app(SepaQrCodeService::class)->buildPayload($company, 1000, 'FA-1'))->toBeNull();
});
