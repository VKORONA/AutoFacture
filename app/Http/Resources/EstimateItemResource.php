<?php

namespace Crater\Http\Resources;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Crater\Models\EstimateLinePhoto;
use Illuminate\Http\Resources\Json\JsonResource;

class EstimateItemResource extends JsonResource
{
    public function toArray($request)
    {
        $photos = EstimateLinePhoto::query()
            ->where('estimate_id', $this->estimate_id)
            ->where('line_uuid', $this->line_uuid)
            ->orderBy('sort_order')
            ->get();
        $activityType = BusinessActivityType::tryFrom(
            (string) ($this->business_activity_type ?: BusinessActivityType::SERVICE_BIC->value)
        ) ?? BusinessActivityType::SERVICE_BIC;

        return [
            'id' => $this->id,
            'line_uuid' => $this->line_uuid,
            'name' => $this->name,
            'description' => $this->description,
            'business_activity_type' => $activityType->value,
            'business_activity_label' => $activityType->label(),
            'discount_type' => $this->discount_type,
            'quantity' => $this->quantity,
            'unit_name' => $this->unit_name,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'price' => $this->price,
            'tax' => $this->tax,
            'total' => $this->total,
            'item_id' => $this->item_id,
            'estimate_id' => $this->estimate_id,
            'company_id' => $this->company_id,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_price' => $this->base_price,
            'base_tax' => $this->base_tax,
            'base_total' => $this->base_total,
            'line_photos' => EstimateLinePhotoResource::collection($photos),
            'taxes' => $this->when($this->taxes()->exists(), function () {
                return TaxResource::collection($this->taxes);
            }),
            'fields' => $this->when($this->fields()->exists(), function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
        ];
    }
}
