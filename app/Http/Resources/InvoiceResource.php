<?php

namespace Crater\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'client_request_id' => $this->client_request_id,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'invoice_number' => $this->invoice_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'paid_status' => $this->paid_status,
            'tax_per_item' => $this->tax_per_item,
            'discount_per_item' => $this->discount_per_item,
            'notes' => $this->notes,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'sub_total' => $this->sub_total,
            'total' => $this->total,
            'tax' => $this->tax,
            'due_amount' => $this->due_amount,
            'credited_amount' => (int) ($this->credited_amount ?? 0),
            'creditable_amount' => max(0, (int) $this->total - (int) ($this->credited_amount ?? 0)),
            'sent' => $this->sent,
            'viewed' => $this->viewed,
            'unique_hash' => $this->unique_hash,
            'template_name' => $this->template_name,
            'customer_id' => $this->customer_id,
            'recurring_invoice_id' => $this->recurring_invoice_id,
            'sequence_number' => $this->sequence_number,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_sub_total' => $this->base_sub_total,
            'base_total' => $this->base_total,
            'creator_id' => $this->creator_id,
            'base_tax' => $this->base_tax,
            'base_due_amount' => $this->base_due_amount,
            'currency_id' => $this->currency_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'invoice_pdf_url' => $this->invoicePdfUrl,
            'formatted_invoice_date' => $this->formattedInvoiceDate,
            'formatted_due_date' => $this->formattedDueDate,
            'allow_edit' => ! $this->finalized_at && $this->allow_edit,
            'is_finalized' => (bool) $this->finalized_at,
            'finalized_at' => $this->finalized_at,
            'finalized_by' => $this->finalized_by,
            'immutable_hash' => $this->immutable_hash,
            'payment_module_enabled' => $this->payment_module_enabled,
            'sales_tax_type' => $this->sales_tax_type,
            'sales_tax_address_type' => $this->sales_tax_address_type,
            'overdue' => $this->overdue,
            'items' => $this->when($this->items()->exists(), fn () => InvoiceItemResource::collection($this->items)),
            'customer' => $this->when($this->customer()->exists(), fn () => new CustomerResource($this->customer)),
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'taxes' => $this->when($this->taxes()->exists(), fn () => TaxResource::collection($this->taxes)),
            'fields' => $this->when($this->fields()->exists(), fn () => CustomFieldValueResource::collection($this->fields)),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'currency' => $this->when($this->currency()->exists(), fn () => new CurrencyResource($this->currency)),
        ];
    }
}
