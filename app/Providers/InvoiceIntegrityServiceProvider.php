<?php

namespace Crater\Providers;

use Crater\Exceptions\FinalizedInvoiceMutationException;
use Crater\Models\CreditNote;
use Crater\Models\CreditNoteItem;
use Crater\Models\Invoice;
use DomainException;
use Illuminate\Support\ServiceProvider;

class InvoiceIntegrityServiceProvider extends ServiceProvider
{
    private const OPERATIONAL_FIELDS = [
        'status',
        'paid_status',
        'due_amount',
        'base_due_amount',
        'overdue',
        'viewed',
        'sent',
        'credited_amount',
        'updated_at',
    ];

    public function boot(): void
    {
        Invoice::updating(function (Invoice $invoice): void {
            if (! $invoice->getOriginal('finalized_at')) {
                return;
            }

            $forbidden = array_diff(array_keys($invoice->getDirty()), self::OPERATIONAL_FIELDS);

            if ($forbidden !== []) {
                throw new FinalizedInvoiceMutationException(
                    'La facture est scellée. Champs interdits : '.implode(', ', $forbidden).'. Créez un avoir.'
                );
            }
        });

        Invoice::deleting(function (Invoice $invoice): void {
            if ($invoice->finalized_at) {
                throw new FinalizedInvoiceMutationException();
            }
        });

        CreditNote::updating(fn () => throw new DomainException('Un avoir émis est immuable.'));
        CreditNote::deleting(fn () => throw new DomainException('Un avoir émis ne peut pas être supprimé.'));
        CreditNoteItem::updating(fn () => throw new DomainException('Une ligne d’avoir émise est immuable.'));
        CreditNoteItem::deleting(fn () => throw new DomainException('Une ligne d’avoir émise ne peut pas être supprimée.'));
    }
}
