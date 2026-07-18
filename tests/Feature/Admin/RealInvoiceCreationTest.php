<?php

use Crater\Models\CompanySetting;
use Crater\Models\FileDisk;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::findOrFail(1);
    $this->companyId = $user->companies()->firstOrFail()->id;

    $this->withHeaders([
        'company' => $this->companyId,
    ]);

    Sanctum::actingAs($user, ['*']);
});

function realInvoicePayload(array $overrides = []): array
{
    $payload = Invoice::factory()->raw([
        'invoice_number' => 'CLIENT-SIMULATION',
        'taxes' => [Tax::factory()->raw()],
        'items' => [InvoiceItem::factory()->raw()],
    ]);

    return array_merge($payload, [
        'client_request_id' => (string) Str::uuid(),
    ], $overrides);
}

it('creates a persisted invoice, returns it and keeps it after refresh', function () {
    $payload = realInvoicePayload();

    $response = postJson('api/v1/invoices', $payload)
        ->assertCreated()
        ->assertJsonPath('meta.created', true);

    $invoiceId = $response->json('data.id');
    $invoiceNumber = $response->json('data.invoice_number');

    expect($invoiceId)->toBeInt()
        ->and($invoiceNumber)->not->toBe('CLIENT-SIMULATION')
        ->and($response->json('invoice.id'))->toBe($invoiceId)
        ->and($response->json('meta.pdf_status'))->toBeIn(['generated', 'queued', 'failed']);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoiceId,
        'company_id' => $this->companyId,
        'client_request_id' => $payload['client_request_id'],
        'invoice_number' => $invoiceNumber,
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoiceId,
        'name' => $payload['items'][0]['name'],
    ]);

    getJson("api/v1/invoices/{$invoiceId}")
        ->assertOk()
        ->assertJsonPath('data.id', $invoiceId)
        ->assertJsonPath('data.invoice_number', $invoiceNumber);

    getJson('api/v1/invoices?page=1&limit=20')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
        ]);
});

it('returns the existing invoice when the same client request is retried', function () {
    $payload = realInvoicePayload();

    $first = postJson('api/v1/invoices', $payload)
        ->assertCreated()
        ->assertJsonPath('meta.created', true);

    $second = postJson('api/v1/invoices', $payload)
        ->assertOk()
        ->assertJsonPath('meta.created', false);

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(Invoice::query()
            ->where('company_id', $this->companyId)
            ->where('client_request_id', $payload['client_request_id'])
            ->count())
        ->toBe(1);
});

it('recalculates invoice totals on the server', function () {
    $payload = realInvoicePayload([
        'sub_total' => 999999,
        'tax' => 999999,
        'total' => 999999,
    ]);

    $taxPerItem = trim((string) (CompanySetting::getSetting('tax_per_item', $this->companyId) ?? 'NO'));
    $expectedSubTotal = collect($payload['items'])->sum(
        static fn (array $item): int => (int) round((float) ($item['total'] ?? 0))
    );
    $expectedTax = $taxPerItem === 'YES'
        ? collect($payload['items'])->sum(
            static fn (array $item): int => (int) round((float) ($item['tax'] ?? 0))
        )
        : collect($payload['taxes'])->sum(
            static fn (array $tax): int => (int) round((float) ($tax['amount'] ?? 0))
        );
    $expectedTotal = $expectedSubTotal - (int) $payload['discount_val'] + $expectedTax;

    $response = postJson('api/v1/invoices', $payload)->assertCreated();

    $this->assertDatabaseHas('invoices', [
        'id' => $response->json('data.id'),
        'sub_total' => $expectedSubTotal,
        'tax' => $expectedTax,
        'total' => $expectedTotal,
        'due_amount' => $expectedTotal,
    ]);
});

it('refuses a customer outside the active company without creating an invoice', function () {
    $initialCount = Invoice::query()->count();
    $payload = realInvoicePayload(['customer_id' => 999999999]);

    postJson('api/v1/invoices', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id']);

    expect(Invoice::query()->count())->toBe($initialCount);
});

it('keeps the invoice and returns an explicit error when PDF storage fails', function () {
    CompanySetting::setSettings([
        'save_pdf_to_disk' => 'YES',
    ], $this->companyId);

    FileDisk::query()->update(['set_as_default' => false]);

    $payload = realInvoicePayload();

    $response = postJson('api/v1/invoices', $payload)
        ->assertCreated()
        ->assertJsonPath('meta.created', true)
        ->assertJsonPath('meta.pdf_status', 'failed');

    expect($response->json('meta.pdf_error'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('invoices', [
        'id' => $response->json('data.id'),
        'client_request_id' => $payload['client_request_id'],
    ]);
});
