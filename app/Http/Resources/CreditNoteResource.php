<?php

namespace Crater\Http\Resources;

use Crater\Models\CreditNote;
use Crater\Models\CreditNoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class CreditNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $creditNote = $this->resource;

        if (! $creditNote instanceof CreditNote) {
            throw new LogicException('CreditNoteResource attend une instance de CreditNote.');
        }

        $invoice = $creditNote->relationLoaded('invoice') ? $creditNote->invoice : null;
        $customer = $creditNote->relationLoaded('customer') ? $creditNote->customer : null;
        $currency = $creditNote->relationLoaded('currency') ? $creditNote->currency : null;
        $creator = $creditNote->relationLoaded('creator') ? $creditNote->creator : null;

        return [
            'id' => $creditNote->id,
            'credit_note_number' => $creditNote->credit_note_number,
            'invoice_id' => $creditNote->invoice_id,
            'invoice_number' => $invoice?->invoice_number,
            'customer_id' => $creditNote->customer_id,
            'currency_id' => $creditNote->currency_id,
            'issue_date' => optional($creditNote->issue_date)->format('Y-m-d'),
            'formatted_issue_date' => optional($creditNote->issue_date)->format('d/m/Y'),
            'reason' => $creditNote->reason,
            'status' => $creditNote->status,
            'settlement_status' => $creditNote->settlement_status,
            'sub_total' => (int) $creditNote->sub_total,
            'tax' => (int) $creditNote->tax,
            'total' => (int) $creditNote->total,
            'applied_to_balance' => (int) $creditNote->applied_to_balance,
            'refundable_amount' => (int) $creditNote->refundable_amount,
            'exchange_rate' => $creditNote->exchange_rate,
            'finalized_at' => $creditNote->finalized_at,
            'immutable_hash' => $creditNote->immutable_hash,
            'unique_hash' => $creditNote->unique_hash,
            'pdf_url' => $creditNote->pdf_url,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'formatted_invoice_date' => $invoice->formattedInvoiceDate,
                'total' => (int) $invoice->total,
            ] : null,
            'customer' => $customer ? new CustomerResource($customer) : null,
            'currency' => $currency ? new CurrencyResource($currency) : null,
            'creator' => $creator ? new UserResource($creator) : null,
            'items' => $creditNote->relationLoaded('items')
                ? $creditNote->items->map(fn (CreditNoteItem $item): array => [
                    'id' => $item->id,
                    'invoice_item_id' => $item->invoice_item_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => (int) $item->price,
                    'sub_total' => (int) $item->sub_total,
                    'tax' => (int) $item->tax,
                    'total' => (int) $item->total,
                ])
                : [],
        ];
    }
}
