<?php

namespace Crater\Domain\Invoicing;

use Carbon\Carbon;
use Crater\Domain\FrenchInvoicing\FrenchLegalMentionBuilder;
use Crater\Models\Address;
use Crater\Models\Company;
use Crater\Models\Country;
use Crater\Models\CreditNote;
use Crater\Models\Currency;
use Crater\Models\Customer;
use Crater\Models\Invoice;
use Crater\Models\User;
use DomainException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditNoteIssuer
{
    public function __construct(
        private readonly InvoiceFinalizer $finalizer,
        private readonly FrenchLegalMentionBuilder $legalMentionBuilder,
    ) {}

    public function issue(Invoice $invoice, string $reason, ?int $requestedAmount, ?User $user = null): CreditNote
    {
        return DB::transaction(function () use ($invoice, $reason, $requestedAmount, $user): CreditNote {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (! $invoice->finalized_at && $invoice->status === Invoice::STATUS_DRAFT) {
                throw new DomainException('Un avoir ne peut être émis que sur une facture déjà finalisée ou envoyée.');
            }

            $invoice = $this->finalizer->finalize($invoice, $user);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $reason = trim($reason);

            if (mb_strlen($reason) < 3) {
                throw new DomainException('Le motif de l’avoir doit contenir au moins trois caractères.');
            }

            $remaining = max(0, (int) $invoice->total - (int) $invoice->credited_amount);
            $amount = $requestedAmount ?? $remaining;

            if ($remaining === 0) {
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

            [$subTotal, $tax] = $this->allocateAccountingTotals($invoice, $amount, $remaining);

            $previousDueAmount = max(0, (int) $invoice->due_amount);
            $appliedToBalance = min($amount, $previousDueAmount);
            $refundableAmount = $amount - $appliedToBalance;
            $newDueAmount = $previousDueAmount - $appliedToBalance;
            $issuedAt = now();

            $snapshot = $this->buildSnapshot(
                invoice: $invoice,
                number: $number,
                reason: $reason,
                subTotal: $subTotal,
                tax: $tax,
                total: $amount,
                appliedToBalance: $appliedToBalance,
                refundableAmount: $refundableAmount,
                issueDate: $issuedAt->toDateString(),
            );
            $json = json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );

            $creditNote = CreditNote::query()->create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'creator_id' => $user?->id,
                'currency_id' => $invoice->currency_id,
                'credit_note_number' => $number,
                'sequence_number' => $sequence,
                'unique_hash' => (string) Str::uuid(),
                'issue_date' => $issuedAt->toDateString(),
                'reason' => $reason,
                'status' => CreditNote::STATUS_ISSUED,
                'settlement_status' => $refundableAmount > 0
                    ? CreditNote::SETTLEMENT_TO_REFUND
                    : CreditNote::SETTLEMENT_APPLIED,
                'exchange_rate' => $invoice->exchange_rate,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $amount,
                'applied_to_balance' => $appliedToBalance,
                'refundable_amount' => $refundableAmount,
                'finalized_at' => $issuedAt,
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
                'due_amount' => $newDueAmount,
                'base_due_amount' => (int) round($newDueAmount * (float) $invoice->exchange_rate),
            ])->save();

            $creditNote->load(['items', 'invoice', 'customer.currency', 'currency']);

            return $creditNote;
        }, 3);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function allocateAccountingTotals(Invoice $invoice, int $amount, int $remaining): array
    {
        $alreadyCreditedTax = (int) CreditNote::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->where('invoice_id', $invoice->id)
            ->sum('tax');

        $remainingTax = max(0, min($remaining, (int) $invoice->tax - $alreadyCreditedTax));
        $tax = $amount === $remaining
            ? $remainingTax
            : (int) round($amount * ($remainingTax / max(1, $remaining)));
        $tax = max(0, min($amount, $remainingTax, $tax));

        return [$amount - $tax, $tax];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(
        Invoice $invoice,
        string $number,
        string $reason,
        int $subTotal,
        int $tax,
        int $total,
        int $appliedToBalance,
        int $refundableAmount,
        string $issueDate,
    ): array {
        $company = Company::query()->findOrFail($invoice->company_id);
        $customer = Customer::query()->findOrFail($invoice->customer_id);
        $currency = Currency::query()->findOrFail($invoice->currency_id);
        $companyAddress = Address::query()
            ->where('company_id', $company->id)
            ->first();
        $billingAddress = Address::query()
            ->where('customer_id', $customer->id)
            ->where('type', Address::BILLING_TYPE)
            ->first();
        $rawInvoiceDate = $invoice->getRawOriginal('invoice_date');

        return [
            'schema' => 'autofacture.credit-note.snapshot.v3',
            'credit_note' => [
                'number' => $number,
                'issue_date' => $issueDate,
                'reason' => $reason,
                'status' => CreditNote::STATUS_ISSUED,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $total,
                'applied_to_balance' => $appliedToBalance,
                'refundable_amount' => $refundableAmount,
            ],
            'original_invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number,
                'issue_date' => $rawInvoiceDate
                    ? Carbon::parse((string) $rawInvoiceDate)->toDateString()
                    : null,
                'total' => (int) $invoice->total,
            ],
            'currency' => [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
            ],
            'seller' => [
                'name' => $company->name,
                'legal_form' => $company->legal_form,
                'siren' => $company->siren,
                'siret' => $company->siret,
                'vat_number' => $company->vat_number,
                'ape_code' => $company->ape_code,
                'rcs_city' => $company->rcs_city,
                'legal_mentions' => $this->legalMentionBuilder->forCompany($company),
                'address' => $this->addressSnapshot($companyAddress),
            ],
            'buyer' => [
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'siren' => $customer->siren,
                'siret' => $customer->siret,
                'vat_number' => $customer->vat_number,
                'ape_code' => $customer->ape_code,
                'address' => $this->addressSnapshot($billingAddress),
            ],
            'lines' => [[
                'name' => 'Avoir sur facture '.$invoice->invoice_number,
                'description' => $reason,
                'quantity' => 1,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $total,
            ]],
            'tax_breakdown' => [[
                'label' => 'TVA',
                'amount' => $tax,
            ]],
        ];
    }

    /**
     * @return array<string, string|null>|null
     */
    private function addressSnapshot(?Address $address): ?array
    {
        if (! $address) {
            return null;
        }

        $country = $address->country_id
            ? Country::query()->whereKey($address->country_id)->value('name')
            : null;

        return [
            'street_1' => $address->address_street_1,
            'street_2' => $address->address_street_2,
            'postal_code' => $address->zip,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $country ? (string) $country : null,
        ];
    }
}
