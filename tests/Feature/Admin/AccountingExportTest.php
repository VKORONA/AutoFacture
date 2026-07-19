<?php

use Crater\Models\AccountingExportBatch;
use Crater\Models\Invoice;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use ZipArchive;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    Storage::fake('local');

    $this->user = User::findOrFail(1);
    $this->company = $this->user->companies()->firstOrFail();
    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

it('provides safe default accounting settings', function () {
    getJson('/api/v1/accounting')
        ->assertOk()
        ->assertJsonPath('data.settings.sales_journal_code', 'VT')
        ->assertJsonPath('data.settings.customer_control_account', '411000')
        ->assertJsonPath('data.settings.vat_accounts.20', '445712')
        ->assertJsonPath('data.profiles.universal_csv', 'Universel CSV')
        ->assertJsonPath('data.profiles.fec_compatible', 'Journal FEC-compatible');
});

it('allows the owner to configure accountant mappings', function () {
    putJson('/api/v1/accounting/settings', [
        'accounting_mode' => 'accrual',
        'export_profile' => 'pennylane',
        'sales_journal_code' => 'VE',
        'bank_journal_code' => 'BQ',
        'customer_control_account' => '411000',
        'sales_services_account' => '706100',
        'sales_goods_account' => '707100',
        'bank_account' => '512100',
        'rounding_account' => '658000',
        'vat_accounts' => [
            '20' => '445712',
            '10' => '445711',
            '5.5' => '445710',
            '2.1' => '445713',
            '0' => null,
        ],
        'include_payments' => true,
        'include_documents' => true,
        'include_commercial_annexes' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.export_profile', 'pennylane')
        ->assertJsonPath('data.sales_services_account', '706100');
});

it('exports only finalized invoices in a balanced ZIP package', function () {
    $invoice = Invoice::factory()->create([
        'invoice_date' => '2026-03-15',
        'due_date' => '2026-04-15',
        'invoice_number' => 'FA-2026-000001',
        'sequence_number' => 1,
        'sub_total' => 100000,
        'tax' => 20000,
        'total' => 120000,
        'due_amount' => 120000,
        'base_sub_total' => 100000,
        'base_tax' => 20000,
        'base_total' => 120000,
        'base_due_amount' => 120000,
        'exchange_rate' => 1,
        'status' => Invoice::STATUS_SENT,
        'finalized_at' => now(),
        'finalized_by' => $this->user->id,
    ]);

    Tax::factory()->create([
        'invoice_id' => $invoice->id,
        'invoice_item_id' => null,
        'company_id' => $this->company->id,
        'currency_id' => $invoice->currency_id,
        'name' => 'TVA 20 %',
        'percent' => 20,
        'amount' => 20000,
        'base_amount' => 20000,
        'compound_tax' => 0,
    ]);

    Invoice::factory()->create([
        'invoice_date' => '2026-04-01',
        'invoice_number' => 'BROUILLON-2026-1',
        'sequence_number' => 2,
        'status' => Invoice::STATUS_DRAFT,
        'finalized_at' => null,
    ]);

    $response = postJson('/api/v1/accounting/exports', [
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
        'profile' => 'pennylane',
        'include_payments' => false,
        'include_documents' => false,
        'include_commercial_annexes' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.invoice_count', 1)
        ->assertJsonPath('data.credit_note_count', 0)
        ->assertJsonPath('data.payment_count', 0)
        ->assertJsonPath('data.total_debit', 120000)
        ->assertJsonPath('data.total_credit', 120000)
        ->assertJsonPath('data.difference', 0);

    $batch = AccountingExportBatch::findOrFail($response->json('data.id'));
    Storage::disk('local')->assertExists($batch->archive_path);
    expect($batch->archive_sha256)->toHaveLength(64);

    $zip = new ZipArchive;
    expect($zip->open(Storage::disk('local')->path($batch->archive_path)))->toBeTrue();
    expect($zip->locateName('ecritures-universelles.csv'))->not->toBeFalse()
        ->and($zip->locateName('journal-ventes-fec-compatible.txt'))->not->toBeFalse()
        ->and($zip->locateName('ecritures-pennylane.csv'))->not->toBeFalse()
        ->and($zip->locateName('manifest.json'))->not->toBeFalse()
        ->and($zip->locateName('rapport-controle.pdf'))->not->toBeFalse();

    $fec = $zip->getFromName('journal-ventes-fec-compatible.txt');
    $universal = $zip->getFromName('ecritures-universelles.csv');
    $manifest = json_decode($zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $zip->close();

    expect($fec)->toContain("JournalCode\tJournalLib\tEcritureNum")
        ->and($fec)->toContain('FA-2026-000001')
        ->and($fec)->not->toContain('BROUILLON-2026-1')
        ->and($universal)->toContain('NomFichierJustificatif')
        ->and($manifest['control']['balanced'])->toBeTrue()
        ->and($manifest['counts']['invoices'])->toBe(1)
        ->and($manifest['export']['factur_x_included'])->toBeFalse()
        ->and($manifest['notice'])->toContain('ne constitue pas un FEC réglementaire complet');
});
