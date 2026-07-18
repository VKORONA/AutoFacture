<?php

use Crater\Models\Customer;
use Crater\Models\Invoice;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $company = $user->companies()->firstOrFail();

    $this->companyId = $company->id;
    $this->customerId = Customer::query()
        ->where('company_id', $company->id)
        ->firstOrFail()
        ->id;

    $this->withHeaders([
        'company' => $company->id,
    ]);

    Sanctum::actingAs($user, ['*']);
});

it('crée, liste et consulte un avoir depuis l API', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'customer_id' => $this->customerId,
        'status' => Invoice::STATUS_SENT,
        'sent' => true,
        'total' => 1200,
        'sub_total' => 1000,
        'tax' => 200,
        'due_amount' => 1200,
        'base_total' => 1200,
        'base_sub_total' => 1000,
        'base_tax' => 200,
        'base_due_amount' => 1200,
        'exchange_rate' => 1,
    ]);

    $response = postJson("api/v1/invoices/{$invoice->id}/credit-notes", [
        'reason' => 'Remise commerciale validée',
        'amount' => 300,
    ])
        ->assertCreated()
        ->assertJsonPath('data.invoice_id', $invoice->id)
        ->assertJsonPath('data.total', 300)
        ->assertJsonPath('data.applied_to_balance', 300)
        ->assertJsonPath('data.refundable_amount', 0)
        ->assertJsonPath('data.status', 'ISSUED');

    $creditNoteId = $response->json('data.id');

    getJson('api/v1/credit-notes?search=Remise&limit=10')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $creditNoteId)
        ->assertJsonPath('data.0.invoice_number', $invoice->invoice_number);

    getJson("api/v1/credit-notes/{$creditNoteId}")
        ->assertOk()
        ->assertJsonPath('data.id', $creditNoteId)
        ->assertJsonPath('data.reason', 'Remise commerciale validée')
        ->assertJsonPath('data.pdf_url', fn ($value) => str_contains($value, '/credit-notes/pdf/'));
});

it('refuse les données invalides avant toute émission', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'customer_id' => $this->customerId,
        'status' => Invoice::STATUS_SENT,
        'sent' => true,
    ]);

    postJson("api/v1/invoices/{$invoice->id}/credit-notes", [
        'reason' => '  ',
        'amount' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason', 'amount']);
});
