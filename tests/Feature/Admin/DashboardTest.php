<?php

use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::findOrFail(1);
    $this->withHeaders([
        'company' => $user->companies()->firstOrFail()->id,
    ]);
    Sanctum::actingAs($user, ['*']);
});

it('returns the premium fiscal dashboard payload', function () {
    getJson('api/v1/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'fiscal_period' => ['label', 'start', 'end'],
            'total_amount_due',
            'pending_invoice_count',
            'pending_estimate_count',
            'pending_estimate_amount',
            'total_sales_ht',
            'previous_sales_ht',
            'sales_growth_percent',
            'total_receipts',
            'previous_receipts',
            'receipts_growth_percent',
            'chart_data' => [
                'months',
                'invoice_totals',
                'previous_invoice_totals',
                'receipt_totals',
            ],
            'invoice_distribution' => [
                'paid',
                'pending',
                'overdue',
                'credit_notes',
            ],
            'recent_due_invoices',
            'recent_estimates',
        ])
        ->assertJsonCount(12, 'chart_data.months')
        ->assertJsonCount(12, 'chart_data.invoice_totals')
        ->assertJsonCount(12, 'chart_data.previous_invoice_totals')
        ->assertJsonCount(12, 'chart_data.receipt_totals');
});

it('keeps the global search endpoint available', function () {
    getJson('api/v1/search?name=ab')->assertOk();
});
