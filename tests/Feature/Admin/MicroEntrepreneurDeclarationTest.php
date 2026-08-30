<?php

use Carbon\Carbon;
use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Crater\Domain\MicroEntrepreneur\MicroEntrepreneurRateResolver;
use Crater\Domain\MicroEntrepreneur\MicroEntrepreneurTurnoverCalculator;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Item;
use Crater\Models\MicroEntrepreneurSetting;
use Crater\Models\Payment;
use Crater\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::findOrFail(1);
    $this->company = $this->user->companies()->firstOrFail();
    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

it('requires and stores an activity category on catalog products', function () {
    $response = postJson('/api/v1/items', [
        'name' => 'Étude thermique',
        'price' => 50000,
        'description' => 'Prestation intellectuelle',
        'business_activity_type' => BusinessActivityType::SERVICE_BNC->value,
    ])->assertOk();

    expect($response->json('data.business_activity_type'))
        ->toBe(BusinessActivityType::SERVICE_BNC->value);

    $this->assertDatabaseHas('items', [
        'id' => $response->json('data.id'),
        'company_id' => $this->company->id,
        'business_activity_type' => BusinessActivityType::SERVICE_BNC->value,
    ]);
});

it('snapshots a catalog classification on invoice and estimate lines', function () {
    $catalogItem = Item::factory()->create([
        'company_id' => $this->company->id,
        'business_activity_type' => BusinessActivityType::GOODS_BIC->value,
    ]);
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'exchange_rate' => 1,
    ]);

    $line = $invoice->items()->create([
        'company_id' => $this->company->id,
        'item_id' => $catalogItem->id,
        'name' => 'Matériel',
        'description' => null,
        'business_activity_type' => BusinessActivityType::SERVICE_BIC->value,
        'quantity' => 1,
        'price' => 10000,
        'discount' => 0,
        'discount_type' => 'fixed',
        'discount_val' => 0,
        'tax' => 0,
        'total' => 10000,
        'exchange_rate' => 1,
        'base_price' => 10000,
        'base_discount_val' => 0,
        'base_tax' => 0,
        'base_total' => 10000,
    ]);

    expect($line->fresh()->business_activity_type)
        ->toBe(BusinessActivityType::GOODS_BIC->value);
});

it('calculates declared turnover from actual payments and splits mixed invoices', function () {
    MicroEntrepreneurSetting::forCompany($this->company->id)->update([
        'enabled' => true,
        'cfp_profile' => 'commercial',
        'versement_liberatoire' => false,
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'invoice_date' => '2026-03-01',
        'sub_total' => 10000,
        'tax' => 2000,
        'total' => 12000,
        'due_amount' => 6000,
        'exchange_rate' => 1,
        'base_sub_total' => 10000,
        'base_tax' => 2000,
        'base_total' => 12000,
        'base_due_amount' => 6000,
        'base_discount_val' => 0,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'company_id' => $this->company->id,
        'name' => 'Fourniture',
        'business_activity_type' => BusinessActivityType::GOODS_BIC->value,
        'quantity' => 1,
        'price' => 4000,
        'discount' => 0,
        'discount_type' => 'fixed',
        'discount_val' => 0,
        'tax' => 800,
        'total' => 4000,
        'exchange_rate' => 1,
        'base_price' => 4000,
        'base_discount_val' => 0,
        'base_tax' => 800,
        'base_total' => 4000,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'company_id' => $this->company->id,
        'name' => 'Pose',
        'business_activity_type' => BusinessActivityType::SERVICE_BIC->value,
        'quantity' => 1,
        'price' => 6000,
        'discount' => 0,
        'discount_type' => 'fixed',
        'discount_val' => 0,
        'tax' => 1200,
        'total' => 6000,
        'exchange_rate' => 1,
        'base_price' => 6000,
        'base_discount_val' => 0,
        'base_tax' => 1200,
        'base_total' => 6000,
    ]);

    Payment::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $invoice->customer_id,
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-03-15',
        'amount' => 6000,
        'base_amount' => 6000,
        'exchange_rate' => 1,
        'currency_id' => $invoice->currency_id,
    ]);

    $report = app(MicroEntrepreneurTurnoverCalculator::class)->calculate(
        $this->company->id,
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-12-31'),
    );
    $march = collect($report['months'])->firstWhere('month', '2026-03');

    expect($march['activities']['goods_bic']['turnover'])->toBe(2000)
        ->and($march['activities']['service_bic']['turnover'])->toBe(3000)
        ->and($march['totals']['turnover'])->toBe(5000)
        ->and($march['activities']['goods_bic']['social_contributions'])->toBe(246)
        ->and($march['activities']['service_bic']['social_contributions'])->toBe(636)
        ->and($march['totals']['income_tax'])->toBe(0);
});

it('uses the 2026 BIC, BNC and Cipav rates and optional versement libératoire', function () {
    $settings = MicroEntrepreneurSetting::forCompany($this->company->id);
    $settings->update(['versement_liberatoire' => true]);
    $resolver = app(MicroEntrepreneurRateResolver::class);
    $date = Carbon::parse('2026-07-01');

    expect($resolver->resolve(BusinessActivityType::GOODS_BIC, $date, $settings)['social_rate'])->toBe(0.123)
        ->and($resolver->resolve(BusinessActivityType::SERVICE_BIC, $date, $settings)['social_rate'])->toBe(0.212)
        ->and($resolver->resolve(BusinessActivityType::SERVICE_BNC, $date, $settings)['social_rate'])->toBe(0.256)
        ->and($resolver->resolve(BusinessActivityType::SERVICE_BNC_CIPAV, $date, $settings)['social_rate'])->toBe(0.232)
        ->and($resolver->resolve(BusinessActivityType::SERVICE_BIC, $date, $settings)['income_tax_rate'])->toBe(0.017);
});

it('updates settings and includes manual adjustments in the API report', function () {
    putJson('/api/v1/micro-entrepreneur/settings', [
        'enabled' => true,
        'declaration_frequency' => 'quarterly',
        'cfp_profile' => 'artisan',
        'versement_liberatoire' => true,
        'acre_enabled' => false,
        'acre_end_date' => null,
        'acre_rate_factor' => 0.5,
        'rate_overrides' => [],
    ])->assertOk();

    postJson('/api/v1/micro-entrepreneur/adjustments', [
        'adjustment_date' => '2026-04-20',
        'business_activity_type' => BusinessActivityType::SERVICE_BNC->value,
        'amount' => 12500,
        'label' => 'Encaissement antérieur importé',
    ])->assertCreated();

    getJson('/api/v1/micro-entrepreneur?year=2026')
        ->assertOk()
        ->assertJsonPath('settings.declaration_frequency', 'quarterly')
        ->assertJsonPath('settings.cfp_profile', 'artisan')
        ->assertJsonPath('months.3.activities.service_bnc.turnover', 12500)
        ->assertJsonPath('adjustments.0.label', 'Encaissement antérieur importé');
});
