<?php

use Crater\Models\Estimate;
use Crater\Models\EstimateItem;
use Crater\Models\Invoice;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::findOrFail(1);
    $this->company = $user->companies()->firstOrFail();

    $this->withHeaders([
        'company' => $this->company->id,
    ]);

    Sanctum::actingAs($user, ['*']);
});

test('a created estimate is returned in both API shapes and persists in the list', function () {
    $payload = Estimate::factory()->raw([
        'estimate_number' => 'DEV-PERSIST-000001',
        'items' => [
            EstimateItem::factory()->raw(),
        ],
        'taxes' => [
            Tax::factory()->raw(),
        ],
    ]);

    $response = postJson('api/v1/estimates', $payload)
        ->assertCreated()
        ->assertJsonPath('data.estimate_number', 'DEV-PERSIST-000001')
        ->assertJsonPath('estimate.estimate_number', 'DEV-PERSIST-000001');

    $estimateId = (int) $response->json('data.id');

    expect($response->json('estimate.id'))->toBe($estimateId);

    $this->assertDatabaseHas('estimates', [
        'id' => $estimateId,
        'company_id' => $this->company->id,
        'estimate_number' => 'DEV-PERSIST-000001',
    ]);

    getJson('api/v1/estimates?page=1&limit=100')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $estimateId,
            'estimate_number' => 'DEV-PERSIST-000001',
        ]);

    getJson("api/v1/estimates/{$estimateId}")
        ->assertOk()
        ->assertJsonPath('data.id', $estimateId);
});

test('converting an estimate creates one durable invoice and preserves its contents', function () {
    $estimate = Estimate::factory()
        ->hasItems(2)
        ->hasTaxes(1)
        ->create([
            'company_id' => $this->company->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

    $estimate->load(['items', 'taxes']);

    $firstResponse = postJson("api/v1/estimates/{$estimate->id}/convert-to-invoice")
        ->assertOk()
        ->assertJsonPath('data.source_estimate_id', $estimate->id);

    $invoiceId = (int) $firstResponse->json('data.id');

    $this->assertDatabaseHas('invoices', [
        'id' => $invoiceId,
        'company_id' => $this->company->id,
        'source_estimate_id' => $estimate->id,
        'customer_id' => $estimate->customer_id,
        'sub_total' => $estimate->sub_total,
        'tax' => $estimate->tax,
        'total' => $estimate->total,
    ]);

    expect(
        Invoice::findOrFail($invoiceId)->items()->count()
    )->toBe($estimate->items->count());

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoiceId,
        'name' => $estimate->items->first()->name,
        'quantity' => $estimate->items->first()->quantity,
        'price' => $estimate->items->first()->price,
        'total' => $estimate->items->first()->total,
    ]);

    $this->assertDatabaseHas('estimates', [
        'id' => $estimate->id,
        'converted_invoice_id' => $invoiceId,
        'status' => Estimate::STATUS_ACCEPTED,
    ]);

    expect(Estimate::findOrFail($estimate->id)->converted_at)->not->toBeNull();

    $secondResponse = postJson("api/v1/estimates/{$estimate->id}/convert-to-invoice")
        ->assertOk()
        ->assertJsonPath('data.id', $invoiceId);

    expect(
        Invoice::query()
            ->where('company_id', $this->company->id)
            ->where('source_estimate_id', $estimate->id)
            ->count()
    )->toBe(1);

    expect((int) $secondResponse->json('data.id'))->toBe($invoiceId);
});
