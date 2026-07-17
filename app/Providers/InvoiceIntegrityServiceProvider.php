<?php

namespace Crater\Providers;

use Crater\Exceptions\FinalizedInvoiceMutationException;
use Crater\Models\Invoice;
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
    }
}
