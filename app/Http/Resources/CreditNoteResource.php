<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'credit_note_number' => $this->credit_note_number,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'customer_id' => $this->customer_id,
            'currency_id' => $this->currency_id,
            'issue_date' => optional($this->issue_date)->format('Y-m-d'),
            'reason' => $this->reason,
            'status' => $this->status,
            'sub_total' => (int) $this->sub_total,
            'tax' => (int) $this->tax,
            'total' => (int) $this->total,
            'exchange_rate' => $this->exchange_rate,
            'finalized_at' => $this->finalized_at,
            'immutable_hash' => $this->immutable_hash,
            'unique_hash' => $this->unique_hash,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'invoice_item_id' => $item->invoice_item_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => (int) $item->price,
                'sub_total' => (int) $item->sub_total,
                'tax' => (int) $item->tax,
                'total' => (int) $item->total,
            ]),
        ];
    }
}
