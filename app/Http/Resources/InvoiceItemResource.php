<?php

namespace Crater\Http\Resources;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request)
    {
        $activityType = BusinessActivityType::tryFrom(
            (string) ($this->business_activity_type ?: BusinessActivityType::SERVICE_BIC->value)
        ) ?? BusinessActivityType::SERVICE_BIC;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'business_activity_type' => $activityType->value,
            'business_activity_label' => $activityType->label(),
            'discount_type' => $this->discount_type,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'unit_name' => $this->unit_name,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'tax' => $this->tax,
            'total' => $this->total,
            'invoice_id' => $this->invoice_id,
            'item_id' => $this->item_id,
            'company_id' => $this->company_id,
            'base_price' => $this->base_price,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_tax' => $this->base_tax,
            'base_total' => $this->base_total,
            'recurring_invoice_id' => $this->recurring_invoice_id,
            'taxes' => $this->when($this->taxes()->exists(), function () {
                return TaxResource::collection($this->taxes);
            }),
            'fields' => $this->when($this->fields()->exists(), function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
        ];
    }
}
