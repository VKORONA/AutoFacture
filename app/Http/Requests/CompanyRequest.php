<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies')->ignore($this->header('company'), 'id'),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'legal_form' => ['nullable', 'string', 'max:40'],
            'siren' => ['nullable', 'regex:/^\d{9}$/'],
            'siret' => ['nullable', 'regex:/^\d{14}$/'],
            'vat_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{2}[A-Z0-9]{2,18}$/i'],
            'ape_code' => ['nullable', 'string', 'max:8'],
            'rcs_city' => ['nullable', 'string', 'max:100'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^[A-Z]{2}[0-9A-Z]{13,32}$/i'],
            'bic' => ['nullable', 'string', 'max:11', 'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/i'],
            'vat_regime' => ['required', Rule::in(['standard', 'franchise_base', 'exempt'])],
            'vat_exempt' => ['boolean'],
            'electronic_invoicing_email' => ['nullable', 'email', 'max:255'],
            'address.country_id' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'siren' => $this->digitsOnly($this->siren),
            'siret' => $this->digitsOnly($this->siret),
            'vat_number' => $this->upperCompact($this->vat_number),
            'ape_code' => $this->upperCompact($this->ape_code),
            'iban' => $this->upperCompact($this->iban),
            'bic' => $this->upperCompact($this->bic),
            'vat_exempt' => filter_var($this->vat_exempt, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function getCompanyPayload()
    {
        return collect($this->validated())
            ->only([
                'name',
                'slug',
                'legal_form',
                'siren',
                'siret',
                'vat_number',
                'ape_code',
                'rcs_city',
                'share_capital',
                'iban',
                'bic',
                'vat_regime',
                'vat_exempt',
                'electronic_invoicing_email',
            ])
            ->toArray();
    }

    private function digitsOnly($value)
    {
        return $value === null ? null : preg_replace('/\D+/', '', (string) $value);
    }

    private function upperCompact($value)
    {
        return $value === null ? null : strtoupper(preg_replace('/\s+/', '', (string) $value));
    }
}
