<?php

namespace Crater\Http\Resources;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        $activityType = $this->business_activity_type instanceof BusinessActivityType
            ? $this->business_activity_type
            : BusinessActivityType::tryFrom((string) $this->business_activity_type)
                ?? BusinessActivityType::SERVICE_BIC;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'business_activity_type' => $activityType->value,
            'business_activity_label' => $activityType->label(),
            'price' => $this->price,
            'unit_id' => $this->unit_id,
            'company_id' => $this->company_id,
            'creator_id' => $this->creator_id,
            'currency_id' => $this->currency_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tax_per_item' => $this->tax_per_item,
            'formatted_created_at' => $this->formattedCreatedAt,
            'unit' => $this->when($this->unit()->exists(), function () {
                return new UnitResource($this->unit);
            }),
            'company' => $this->when($this->company()->exists(), function () {
                return new CompanyResource($this->company);
            }),
            'taxes' => $this->when($this->taxes()->exists(), function () {
                return TaxResource::collection($this->taxes);
            }),
            'currency' => $this->when($this->currency()->exists(), function () {
                return new CurrencyResource($this->currency);
            }),
        ];
    }
}
