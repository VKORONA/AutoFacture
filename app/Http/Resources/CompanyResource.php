<?php

namespace Crater\Http\Resources;

use Crater\Models\FileDisk;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray($request)
    {
        $hasDefaultFileDisk = FileDisk::query()
            ->whereSetAsDefault(true)
            ->exists();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_form' => $this->legal_form,
            'siren' => $this->siren,
            'siret' => $this->siret,
            'vat_number' => $this->vat_number,
            'ape_code' => $this->ape_code,
            'rcs_city' => $this->rcs_city,
            'share_capital' => $this->share_capital,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'vat_regime' => $this->vat_regime,
            'vat_exempt' => (bool) $this->vat_exempt,
            'electronic_invoicing_email' => $this->electronic_invoicing_email,
            'logo' => $this->logo,
            'logo_path' => $hasDefaultFileDisk ? $this->logo_path : null,
            'unique_hash' => $this->unique_hash,
            'owner_id' => $this->owner_id,
            'slug' => $this->slug,
            'address' => $this->when($this->address()->exists(), function () {
                return new AddressResource($this->address);
            }),
            'roles' => RoleResource::collection($this->roles),
        ];
    }
}
