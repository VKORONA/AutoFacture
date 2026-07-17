<?php

namespace Crater\Domain\Invoicing;

use Crater\Models\Company;
use Crater\Models\CreditNote;
use Crater\Models\Invoice;
use Crater\Models\User;
use DomainException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditNoteIssuer
{
    public function __construct(private InvoiceFinalizer $finalizer)
    {
    }

    public function issue(Invoice $invoice, string $reason, ?int $requestedAmount, ?User $user = null): CreditNote
    {
        return DB::transaction(function () use ($invoice, $reason, $requestedAmount, $user): CreditNote {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (! $invoice->finalized_at && $invoice->status === Invoice::STATUS_DRAFT) {
                throw new DomainException('Un avoir ne peut être émis que sur une facture déjà finalisée ou envoyée.');
            }

            $invoice = $this->finalizer->finalize($invoice, $user);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $remaining = (int) $invoice->total - (int) $invoice->credited_amount;
            $amount = $requestedAmount ?? $remaining;

            if ($remaining <= 0) {
                throw new DomainException('Cette facture a déjà été intégralement créditée.');
            }

            if ($amount <= 0 || $amount > $remaining) {
                throw new DomainException("Le montant de l’avoir doit être compris entre 1 et {$remaining} centimes.");
            }

            Company::query()->whereKey($invoice->company_id)->lockForUpdate()->firstOrFail();

            $sequence = (int) CreditNote::withoutGlobalScopes()
                ->where('company_id', $invoice->company_id)
                ->max('sequence_number') + 1;
            $number = 'AV-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $subTotal = $amount === (int) $invoice->total
                ? (int) $invoice->sub_total
                : (int) round($amount * ((int) $invoice->sub_total / max(1, (int) $invoice->total)));
            $tax = $amount - $subTotal;

            $snapshot = [
                'schema' => 'autofacture.credit-note.snapshot.v1',
                'credit_note_number' => $number,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'currency_id' => $invoice->currency_id,
                'issue_date' => now()->toDateString(),
                'reason' => $reason,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $amount,
            ];
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            $creditNote = CreditNote::query()->create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'creator_id' => $user?->id,
                'currency_id' => $invoice->currency_id,
                'credit_note_number' => $number,
                'sequence_number' => $sequence,
                'unique_hash' => (string) Str::uuid(),
                'issue_date' => now()->toDateString(),
                'reason' => $reason,
                'status' => CreditNote::STATUS_ISSUED,
                'exchange_rate' => $invoice->exchange_rate,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $amount,
                'finalized_at' => now(),
                'immutable_hash' => hash('sha256', $json),
                'finalized_snapshot' => Crypt::encryptString($json),
            ]);

            $creditNote->items()->create([
                'company_id' => $invoice->company_id,
                'name' => 'Avoir sur facture '.$invoice->invoice_number,
                'description' => $reason,
                'quantity' => 1,
                'price' => $subTotal,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $amount,
            ]);

            $invoice->forceFill([
                'credited_amount' => (int) $invoice->credited_amount + $amount,
            ])->save();

            return $creditNote->fresh(['items', 'invoice', 'customer', 'currency']);
        }, 3);
    }
}
