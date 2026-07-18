<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_note_number' => $this->credit_note_number,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'customer_id' => $this->customer_id,
            'currency_id' => $this->currency_id,
            'issue_date' => optional($this->issue_date)->format('Y-m-d'),
            'formatted_issue_date' => optional($this->issue_date)->format('d/m/Y'),
            'reason' => $this->reason,
            'status' => $this->status,
            'settlement_status' => $this->settlement_status,
            'sub_total' => (int) $this->sub_total,
            'tax' => (int) $this->tax,
            'total' => (int) $this->total,
            'applied_to_balance' => (int) $this->applied_to_balance,
            'refundable_amount' => (int) $this->refundable_amount,
            'exchange_rate' => $this->exchange_rate,
            'finalized_at' => $this->finalized_at,
            'immutable_hash' => $this->immutable_hash,
            'unique_hash' => $this->unique_hash,
            'pdf_url' => $this->pdf_url,
            'invoice' => $this->whenLoaded('invoice', fn (): array => [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'invoice_date' => optional($this->invoice->invoice_date)->format('Y-m-d'),
                'formatted_invoice_date' => $this->invoice->formattedInvoiceDate,
                'total' => (int) $this->invoice->total,
            ]),
            'customer' => $this->whenLoaded(
                'customer',
                fn (): CustomerResource => new CustomerResource($this->customer),
            ),
            'currency' => $this->whenLoaded(
                'currency',
                fn (): CurrencyResource => new CurrencyResource($this->currency),
            ),
            'creator' => $this->whenLoaded(
                'creator',
                fn (): UserResource => new UserResource($this->creator),
            ),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'invoice_item_id' => $item->invoice_item_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => (int) $item->price,
                'sub_total' => (int) $item->sub_total,
                'tax' => (int) $item->tax,
                'total' => (int) $item->total,
            ])),
        ];
    }
}
