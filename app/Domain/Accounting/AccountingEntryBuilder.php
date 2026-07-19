<?php

namespace Crater\Domain\Accounting;

use Crater\Models\AccountingSetting;
use Crater\Models\CreditNote;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Crater\Models\Tax;
use Illuminate\Database\Eloquent\Collection;

class AccountingEntryBuilder
{
    /**
     * @return array{
     *     entries: array<int, array<string, mixed>>,
     *     invoices: Collection<int, Invoice>,
     *     credit_notes: Collection<int, CreditNote>,
     *     payments: Collection<int, Payment>,
     *     totals: array{debit: int, credit: int, difference: int}
     * }
     */
    public function build(
        int $companyId,
        string $periodStart,
        string $periodEnd,
        AccountingSetting $settings,
        bool $includePayments = true,
    ): array {
        $invoices = Invoice::query()
            ->where('company_id', $companyId)
            ->whereNotNull('finalized_at')
            ->whereBetween('invoice_date', [$periodStart, $periodEnd])
            ->with(['customer', 'currency', 'taxes', 'items.taxes'])
            ->orderBy('invoice_date')
            ->orderBy('sequence_number')
            ->get();

        $creditNotes = CreditNote::query()
            ->where('company_id', $companyId)
            ->whereNotNull('finalized_at')
            ->whereBetween('issue_date', [$periodStart, $periodEnd])
            ->with(['customer', 'currency', 'invoice.taxes', 'invoice.items.taxes'])
            ->orderBy('issue_date')
            ->orderBy('sequence_number')
            ->get();

        $payments = $includePayments
            ? Payment::query()
                ->where('company_id', $companyId)
                ->whereBetween('payment_date', [$periodStart, $periodEnd])
                ->with(['customer', 'currency', 'invoice'])
                ->orderBy('payment_date')
                ->orderBy('sequence_number')
                ->get()
            : new Collection;

        $entries = [];

        foreach ($invoices as $invoice) {
            array_push($entries, ...$this->invoiceEntries($invoice, $settings));
        }

        foreach ($creditNotes as $creditNote) {
            array_push($entries, ...$this->creditNoteEntries($creditNote, $settings));
        }

        foreach ($payments as $payment) {
            array_push($entries, ...$this->paymentEntries($payment, $settings));
        }

        $debit = (int) collect($entries)->sum('debit');
        $credit = (int) collect($entries)->sum('credit');

        return [
            'entries' => $entries,
            'invoices' => $invoices,
            'credit_notes' => $creditNotes,
            'payments' => $payments,
            'totals' => [
                'debit' => $debit,
                'credit' => $credit,
                'difference' => $debit - $credit,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function invoiceEntries(Invoice $invoice, AccountingSetting $settings): array
    {
        $date = $this->rawDate($invoice, 'invoice_date');
        $number = (string) $invoice->invoice_number;
        $customerName = $this->customerName($invoice->customer);
        $label = trim('Facture '.$number.' '.$customerName);
        $entryNumber = $settings->sales_journal_code.'-FA-'.str_pad((string) $invoice->sequence_number, 8, '0', STR_PAD_LEFT);
        $total = (int) $invoice->total;
        $taxTotal = (int) $invoice->tax;
        $net = $total - $taxTotal;
        $taxGroups = $this->invoiceTaxGroups($invoice, $taxTotal);
        $currencyCode = (string) ($invoice->currency?->code ?? 'EUR');
        $aux = $this->customerAuxiliary((int) $invoice->customer_id);

        $entries = [
            $this->entry(
                journalCode: (string) $settings->sales_journal_code,
                journalLabel: 'Journal des ventes',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: (string) $settings->customer_control_account,
                accountLabel: 'Clients',
                auxiliaryNumber: $aux,
                auxiliaryLabel: $customerName,
                pieceReference: $number,
                label: $label,
                debit: $total,
                credit: 0,
                currencyCode: $currencyCode,
                sourceType: 'invoice',
                sourceId: (int) $invoice->id,
                dueDate: $this->rawDate($invoice, 'due_date'),
                amountHt: $net,
                amountVat: $taxTotal,
                amountTtc: $total,
                documentFilename: $number.'.pdf',
            ),
            $this->entry(
                journalCode: (string) $settings->sales_journal_code,
                journalLabel: 'Journal des ventes',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: (string) $settings->sales_services_account,
                accountLabel: 'Prestations de services',
                auxiliaryNumber: '',
                auxiliaryLabel: '',
                pieceReference: $number,
                label: $label,
                debit: 0,
                credit: $net,
                currencyCode: $currencyCode,
                sourceType: 'invoice',
                sourceId: (int) $invoice->id,
                dueDate: $this->rawDate($invoice, 'due_date'),
                amountHt: $net,
                amountVat: 0,
                amountTtc: $total,
                documentFilename: $number.'.pdf',
            ),
        ];

        foreach ($taxGroups as $rate => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $entries[] = $this->entry(
                journalCode: (string) $settings->sales_journal_code,
                journalLabel: 'Journal des ventes',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: $this->vatAccount($settings, $rate),
                accountLabel: 'TVA collectée '.str_replace('.', ',', $rate).' %',
                auxiliaryNumber: '',
                auxiliaryLabel: '',
                pieceReference: $number,
                label: $label,
                debit: 0,
                credit: $amount,
                currencyCode: $currencyCode,
                sourceType: 'invoice',
                sourceId: (int) $invoice->id,
                dueDate: $this->rawDate($invoice, 'due_date'),
                vatRate: $rate,
                amountHt: 0,
                amountVat: $amount,
                amountTtc: $total,
                documentFilename: $number.'.pdf',
            );
        }

        return $this->balanceDocument($entries, $settings, $entryNumber, $date, $number, $label, $currencyCode, 'invoice', (int) $invoice->id);
    }

    /** @return array<int, array<string, mixed>> */
    private function creditNoteEntries(CreditNote $creditNote, AccountingSetting $settings): array
    {
        $date = $this->rawDate($creditNote, 'issue_date');
        $number = (string) $creditNote->credit_note_number;
        $customerName = $this->customerName($creditNote->customer);
        $label = trim('Avoir '.$number.' '.$customerName);
        $entryNumber = $settings->sales_journal_code.'-AV-'.str_pad((string) $creditNote->sequence_number, 8, '0', STR_PAD_LEFT);
        $total = (int) $creditNote->total;
        $taxTotal = (int) $creditNote->tax;
        $net = $total - $taxTotal;
        $currencyCode = (string) ($creditNote->currency?->code ?? 'EUR');
        $aux = $this->customerAuxiliary((int) $creditNote->customer_id);
        $taxGroups = $this->allocateCreditNoteTax($creditNote, $taxTotal);

        $entries = [
            $this->entry(
                journalCode: (string) $settings->sales_journal_code,
                journalLabel: 'Journal des ventes',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: (string) $settings->sales_services_account,
                accountLabel: 'Prestations de services',
                auxiliaryNumber: '',
                auxiliaryLabel: '',
                pieceReference: $number,
                label: $label,
                debit: $net,
                credit: 0,
                currencyCode: $currencyCode,
                sourceType: 'credit_note',
                sourceId: (int) $creditNote->id,
                amountHt: -$net,
                amountVat: 0,
                amountTtc: -$total,
                documentFilename: $number.'.pdf',
            ),
        ];

        foreach ($taxGroups as $rate => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $entries[] = $this->entry(
                journalCode: (string) $settings->sales_journal_code,
                journalLabel: 'Journal des ventes',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: $this->vatAccount($settings, $rate),
                accountLabel: 'TVA collectée '.str_replace('.', ',', $rate).' %',
                auxiliaryNumber: '',
                auxiliaryLabel: '',
                pieceReference: $number,
                label: $label,
                debit: $amount,
                credit: 0,
                currencyCode: $currencyCode,
                sourceType: 'credit_note',
                sourceId: (int) $creditNote->id,
                vatRate: $rate,
                amountHt: 0,
                amountVat: -$amount,
                amountTtc: -$total,
                documentFilename: $number.'.pdf',
            );
        }

        $entries[] = $this->entry(
            journalCode: (string) $settings->sales_journal_code,
            journalLabel: 'Journal des ventes',
            entryNumber: $entryNumber,
            date: $date,
            accountNumber: (string) $settings->customer_control_account,
            accountLabel: 'Clients',
            auxiliaryNumber: $aux,
            auxiliaryLabel: $customerName,
            pieceReference: $number,
            label: $label,
            debit: 0,
            credit: $total,
            currencyCode: $currencyCode,
            sourceType: 'credit_note',
            sourceId: (int) $creditNote->id,
            amountHt: -$net,
            amountVat: -$taxTotal,
            amountTtc: -$total,
            documentFilename: $number.'.pdf',
        );

        return $this->balanceDocument($entries, $settings, $entryNumber, $date, $number, $label, $currencyCode, 'credit_note', (int) $creditNote->id);
    }

    /** @return array<int, array<string, mixed>> */
    private function paymentEntries(Payment $payment, AccountingSetting $settings): array
    {
        $date = $this->rawDate($payment, 'payment_date');
        $number = (string) ($payment->payment_number ?: 'REG-'.$payment->id);
        $customerName = $this->customerName($payment->customer);
        $label = trim('Règlement '.$number.' '.$customerName);
        $entryNumber = $settings->bank_journal_code.'-RG-'.str_pad((string) $payment->sequence_number, 8, '0', STR_PAD_LEFT);
        $amount = (int) $payment->amount;
        $currencyCode = (string) ($payment->currency?->code ?? 'EUR');
        $aux = $this->customerAuxiliary((int) $payment->customer_id);

        return [
            $this->entry(
                journalCode: (string) $settings->bank_journal_code,
                journalLabel: 'Journal de banque',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: (string) $settings->bank_account,
                accountLabel: 'Banque',
                auxiliaryNumber: '',
                auxiliaryLabel: '',
                pieceReference: $number,
                label: $label,
                debit: $amount,
                credit: 0,
                currencyCode: $currencyCode,
                sourceType: 'payment',
                sourceId: (int) $payment->id,
                paymentDate: $date,
                amountTtc: $amount,
                documentFilename: $number.'.pdf',
            ),
            $this->entry(
                journalCode: (string) $settings->bank_journal_code,
                journalLabel: 'Journal de banque',
                entryNumber: $entryNumber,
                date: $date,
                accountNumber: (string) $settings->customer_control_account,
                accountLabel: 'Clients',
                auxiliaryNumber: $aux,
                auxiliaryLabel: $customerName,
                pieceReference: $number,
                label: $label,
                debit: 0,
                credit: $amount,
                currencyCode: $currencyCode,
                sourceType: 'payment',
                sourceId: (int) $payment->id,
                paymentDate: $date,
                amountTtc: $amount,
                documentFilename: $number.'.pdf',
            ),
        ];
    }

    /** @return array<string, int> */
    private function invoiceTaxGroups(Invoice $invoice, int $expectedTax): array
    {
        $taxes = collect($invoice->taxes)
            ->concat($invoice->items->flatMap(fn ($item) => $item->taxes));

        $groups = $taxes
            ->groupBy(fn (Tax $tax): string => $this->normalizeRate((float) $tax->percent))
            ->map(fn ($group): int => (int) $group->sum('amount'))
            ->filter(fn (int $amount): bool => $amount > 0)
            ->all();

        $difference = $expectedTax - array_sum($groups);

        if ($difference !== 0 && $expectedTax > 0) {
            $key = array_key_first($groups) ?? $this->effectiveRate($expectedTax, max(1, (int) $invoice->sub_total));
            $groups[$key] = ($groups[$key] ?? 0) + $difference;
        }

        ksort($groups, SORT_NATURAL);

        return $groups;
    }

    /** @return array<string, int> */
    private function allocateCreditNoteTax(CreditNote $creditNote, int $creditTax): array
    {
        if ($creditTax <= 0) {
            return [];
        }

        $invoice = $creditNote->invoice;
        $sourceGroups = $invoice ? $this->invoiceTaxGroups($invoice, (int) $invoice->tax) : [];
        $sourceTotal = array_sum($sourceGroups);

        if ($sourceTotal <= 0) {
            return [$this->effectiveRate($creditTax, max(1, (int) $creditNote->sub_total)) => $creditTax];
        }

        $allocated = [];
        $remaining = $creditTax;
        $keys = array_keys($sourceGroups);

        foreach ($keys as $index => $rate) {
            $amount = $index === array_key_last($keys)
                ? $remaining
                : (int) round($creditTax * ($sourceGroups[$rate] / $sourceTotal));
            $amount = max(0, min($remaining, $amount));
            $allocated[$rate] = $amount;
            $remaining -= $amount;
        }

        return $allocated;
    }

    /** @param array<int, array<string, mixed>> $entries
     *  @return array<int, array<string, mixed>>
     */
    private function balanceDocument(
        array $entries,
        AccountingSetting $settings,
        string $entryNumber,
        string $date,
        string $piece,
        string $label,
        string $currencyCode,
        string $sourceType,
        int $sourceId,
    ): array {
        $difference = (int) collect($entries)->sum('debit') - (int) collect($entries)->sum('credit');

        if ($difference === 0) {
            return $entries;
        }

        $entries[] = $this->entry(
            journalCode: (string) $settings->sales_journal_code,
            journalLabel: 'Journal des ventes',
            entryNumber: $entryNumber,
            date: $date,
            accountNumber: (string) $settings->rounding_account,
            accountLabel: 'Écarts d’arrondis',
            auxiliaryNumber: '',
            auxiliaryLabel: '',
            pieceReference: $piece,
            label: $label.' - ajustement d’arrondi',
            debit: $difference < 0 ? abs($difference) : 0,
            credit: $difference > 0 ? $difference : 0,
            currencyCode: $currencyCode,
            sourceType: $sourceType,
            sourceId: $sourceId,
        );

        return $entries;
    }

    /** @return array<string, mixed> */
    private function entry(
        string $journalCode,
        string $journalLabel,
        string $entryNumber,
        string $date,
        string $accountNumber,
        string $accountLabel,
        string $auxiliaryNumber,
        string $auxiliaryLabel,
        string $pieceReference,
        string $label,
        int $debit,
        int $credit,
        string $currencyCode,
        string $sourceType,
        int $sourceId,
        ?string $dueDate = null,
        ?string $paymentDate = null,
        ?string $vatRate = null,
        int $amountHt = 0,
        int $amountVat = 0,
        int $amountTtc = 0,
        ?string $documentFilename = null,
    ): array {
        return [
            'journal_code' => $journalCode,
            'journal_label' => $journalLabel,
            'entry_number' => $entryNumber,
            'entry_date' => $date,
            'account_number' => $accountNumber,
            'account_label' => $accountLabel,
            'auxiliary_number' => $auxiliaryNumber,
            'auxiliary_label' => $auxiliaryLabel,
            'piece_reference' => $pieceReference,
            'piece_date' => $date,
            'label' => mb_substr($label, 0, 200),
            'debit' => $debit,
            'credit' => $credit,
            'lettering' => '',
            'lettering_date' => '',
            'validation_date' => $date,
            'currency_amount' => $debit > 0 ? $debit : $credit,
            'currency_code' => $currencyCode,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'vat_rate' => $vatRate,
            'amount_ht' => $amountHt,
            'amount_vat' => $amountVat,
            'amount_ttc' => $amountTtc,
            'due_date' => $dueDate,
            'payment_date' => $paymentDate,
            'document_filename' => $documentFilename,
        ];
    }

    private function vatAccount(AccountingSetting $settings, string $rate): string
    {
        $accounts = $settings->vat_accounts ?? [];

        return (string) ($accounts[$rate] ?? $accounts[(string) (float) $rate] ?? '445712');
    }

    private function customerAuxiliary(int $customerId): string
    {
        return 'C'.str_pad((string) $customerId, 8, '0', STR_PAD_LEFT);
    }

    private function customerName($customer): string
    {
        return trim((string) ($customer?->company_name ?: $customer?->name ?: 'Client'));
    }

    private function normalizeRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 3, '.', ''), '0'), '.');
    }

    private function effectiveRate(int $tax, int $net): string
    {
        return $this->normalizeRate(($tax / max(1, $net)) * 100);
    }

    private function rawDate($model, string $field): string
    {
        $value = $model->getRawOriginal($field) ?: $model->{$field};

        return $value ? date('Ymd', strtotime((string) $value)) : '';
    }
}
