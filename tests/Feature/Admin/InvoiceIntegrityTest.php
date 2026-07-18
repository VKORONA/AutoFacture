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
    $freshInvoice = $invoice->fresh();

    expect($first->credit_note_number)->toStartWith('AV-')
        ->and($first->total)->toBe(400)
        ->and($first->applied_to_balance)->toBe(400)
        ->and($first->refundable_amount)->toBe(0)
        ->and($first->immutable_hash)->toHaveLength(64)
        ->and($second->total)->toBe(600)
        ->and($freshInvoice->credited_amount)->toBe(1000)
        ->and($freshInvoice->due_amount)->toBe(0);

    expect(fn () => $issuer->issue($freshInvoice, 'Dépassement', 1, $user))
        ->toThrow(DomainException::class);
});

it('préserve les totaux HT et TVA malgré les arrondis successifs', function () {
    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $invoice = Invoice::factory()->create([
        'status' => Invoice::STATUS_SENT,
        'sent' => true,
        'total' => 900,
        'sub_total' => 800,
        'tax' => 100,
        'due_amount' => 900,
        'base_total' => 900,
        'base_sub_total' => 800,
        'base_tax' => 100,
        'base_due_amount' => 900,
        'exchange_rate' => 1,
    ]);
    $issuer = app(CreditNoteIssuer::class);

    $first = $issuer->issue($invoice, 'Premier avoir', 333, $user);
    $second = $issuer->issue($invoice->fresh(), 'Solde final', null, $user);

    expect($first->sub_total)->toBe(296)
        ->and($first->tax)->toBe(37)
        ->and($second->sub_total)->toBe(504)
        ->and($second->tax)->toBe(63)
        ->and($first->sub_total + $second->sub_total)->toBe(800)
        ->and($first->tax + $second->tax)->toBe(100)
        ->and($first->total + $second->total)->toBe(900);
});

it('distingue la part imputée du remboursement client à traiter', function () {
    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $invoice = Invoice::factory()->create([
        'status' => Invoice::STATUS_SENT,
        'sent' => true,
        'total' => 1000,
        'sub_total' => 800,
        'tax' => 200,
        'due_amount' => 250,
        'base_total' => 1000,
        'base_sub_total' => 800,
        'base_tax' => 200,
        'base_due_amount' => 250,
        'exchange_rate' => 1,
    ]);

    $creditNote = app(CreditNoteIssuer::class)->issue(
        $invoice,
        'Annulation après règlement partiel',
        400,
        $user,
    );

    expect($creditNote->applied_to_balance)->toBe(250)
        ->and($creditNote->refundable_amount)->toBe(150)
        ->and($creditNote->settlement_status)->toBe('TO_REFUND')
        ->and($invoice->fresh()->due_amount)->toBe(0);
});
