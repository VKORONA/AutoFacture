<?php

use Crater\Domain\Invoicing\CreditNoteIssuer;
use Crater\Domain\Invoicing\InvoiceFinalizer;
use Crater\Exceptions\FinalizedInvoiceMutationException;
use Crater\Models\Invoice;
use Crater\Models\User;
use DomainException;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

it('scelle une facture et bloque ses données comptables', function () {
    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $invoice = Invoice::factory()->create([
        'total' => 1000,
        'sub_total' => 800,
        'tax' => 200,
        'due_amount' => 1000,
        'base_total' => 1000,
        'base_sub_total' => 800,
        'base_tax' => 200,
        'base_due_amount' => 1000,
        'exchange_rate' => 1,
    ]);

    $finalized = app(InvoiceFinalizer::class)->finalize($invoice, $user);

    expect($finalized->finalized_at)->not->toBeNull()
        ->and($finalized->immutable_hash)->toHaveLength(64)
        ->and(app(InvoiceFinalizer::class)->verify($finalized))->toBeTrue();

    $finalized->forceFill(['notes' => 'Modification interdite']);
    expect(fn () => $finalized->save())->toThrow(FinalizedInvoiceMutationException::class);

    $fresh = $finalized->fresh();
    $fresh->forceFill(['due_amount' => 500, 'base_due_amount' => 500])->save();
    expect($fresh->fresh()->due_amount)->toBe(500);

    expect(fn () => $fresh->delete())->toThrow(FinalizedInvoiceMutationException::class);
});

it('refuse un avoir sur une facture brouillon', function () {
    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_DRAFT]);

    expect(fn () => app(CreditNoteIssuer::class)->issue($invoice, 'Motif', null, $user))
        ->toThrow(DomainException::class);
});

it('émet des avoirs partiels sans dépasser le total de la facture', function () {
    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $invoice = Invoice::factory()->create([
        'status' => Invoice::STATUS_SENT,
        'sent' => true,
        'total' => 1000,
        'sub_total' => 800,
        'tax' => 200,
        'due_amount' => 1000,
        'base_total' => 1000,
        'base_sub_total' => 800,
        'base_tax' => 200,
        'base_due_amount' => 1000,
        'exchange_rate' => 1,
    ]);
    $issuer = app(CreditNoteIssuer::class);

    $first = $issuer->issue($invoice, 'Remise commerciale', 400, $user);
    $second = $issuer->issue($invoice->fresh(), 'Annulation du solde', 600, $user);

    expect($first->credit_note_number)->toStartWith('AV-')
        ->and($first->total)->toBe(400)
        ->and($first->immutable_hash)->toHaveLength(64)
        ->and($second->total)->toBe(600)
        ->and($invoice->fresh()->credited_amount)->toBe(1000);

    expect(fn () => $issuer->issue($invoice->fresh(), 'Dépassement', 1, $user))
        ->toThrow(DomainException::class);
});
