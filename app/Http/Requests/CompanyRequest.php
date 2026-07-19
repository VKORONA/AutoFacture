<?php

namespace Crater\Http\Requests;

use Crater\Rules\ValidFrenchBusinessNumber;
use Crater\Rules\ValidIban;
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
        $completeSetup = $this->boolean('complete_setup');

        return [
            'complete_setup' => ['sometimes', 'boolean'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies')->ignore($this->header('company'), 'id'),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'legal_form' => ['nullable', 'string', 'max:40'],
            'siren' => ['nullable', 'regex:/^\d{9}$/', new ValidFrenchBusinessNumber(9, 'SIREN')],
            'siret' => [
                Rule::requiredIf($completeSetup),
                'nullable',
                'regex:/^\d{14}$/',
                new ValidFrenchBusinessNumber(14, 'SIRET'),
            ],
            'vat_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{2}[A-Z0-9]{2,18}$/i'],
            'ape_code' => ['nullable', 'string', 'max:8'],
            'rcs_city' => ['nullable', 'string', 'max:100'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'iban' => ['nullable', 'string', 'max:34', new ValidIban()],
            'bic' => ['nullable', 'string', 'max:11', 'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/i'],
            'vat_regime' => ['required', Rule::in(['standard', 'franchise_base', 'exempt'])],
            'vat_exempt' => ['required', 'boolean'],
            'electronic_invoicing_email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'array'],
            'address.country_id' => ['required'],
            'address.address_street_1' => [Rule::requiredIf($completeSetup), 'nullable', 'string', 'max:255'],
            'address.address_street_2' => ['nullable', 'string', 'max:255'],
            'address.city' => [Rule::requiredIf($completeSetup), 'nullable', 'string', 'max:100'],
            'address.zip' => [Rule::requiredIf($completeSetup), 'nullable', 'string', 'max:20'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.phone' => ['nullable', 'string', 'max:50'],
            'address.website' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->siren && $this->siret && substr($this->siret, 0, 9) !== $this->siren) {
                $validator->errors()->add('siret', 'Le SIRET doit commencer par le SIREN de l’entreprise.');
            }
        });
    }

    protected function prepareForValidation()
    {
        $vatRegime = $this->vat_regime ?: 'standard';
        $siret = $this->digitsOnly($this->siret);
        $siren = $this->digitsOnly($this->siren);

        if (! $siren && $siret && strlen($siret) === 14) {
            $siren = substr($siret, 0, 9);
        }

        $this->merge([
            'siren' => $siren,
            'siret' => $siret,
            'vat_number' => $this->upperCompact($this->vat_number),
            'ape_code' => $this->upperCompact($this->ape_code),
            'iban' => $this->upperCompact($this->iban),
            'bic' => $this->upperCompact($this->bic),
            'vat_regime' => $vatRegime,
            'vat_exempt' => in_array($vatRegime, ['franchise_base', 'exempt'], true),
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
