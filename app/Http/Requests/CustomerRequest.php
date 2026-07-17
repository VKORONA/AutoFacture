<?php

namespace Crater\Http\Requests;

use Crater\Models\Address;
use Crater\Rules\ValidFrenchBusinessNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'customer_type' => ['required', Rule::in(['business', 'individual'])],
            'email' => [
                'email',
                'nullable',
                Rule::unique('customers')->where('company_id', $this->header('company')),
            ],
            'electronic_invoicing_email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'siren' => ['nullable', 'regex:/^\d{9}$/', new ValidFrenchBusinessNumber(9, 'SIREN')],
            'siret' => ['nullable', 'regex:/^\d{14}$/', new ValidFrenchBusinessNumber(14, 'SIRET')],
            'vat_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{2}[A-Z0-9]{2,18}$/i'],
            'ape_code' => ['nullable', 'string', 'max:8'],
            'enable_portal' => ['boolean'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'billing.name' => ['nullable'],
            'billing.address_street_1' => ['nullable'],
            'billing.address_street_2' => ['nullable'],
            'billing.city' => ['nullable'],
            'billing.state' => ['nullable'],
            'billing.country_id' => ['nullable'],
            'billing.zip' => ['nullable'],
            'billing.phone' => ['nullable'],
            'billing.fax' => ['nullable'],
            'shipping.name' => ['nullable'],
            'shipping.address_street_1' => ['nullable'],
            'shipping.address_street_2' => ['nullable'],
            'shipping.city' => ['nullable'],
            'shipping.state' => ['nullable'],
            'shipping.country_id' => ['nullable'],
            'shipping.zip' => ['nullable'],
            'shipping.phone' => ['nullable'],
            'shipping.fax' => ['nullable'],
        ];

        if ($this->isMethod('PUT') && $this->email !== null) {
            $rules['email'] = [
                'email',
                'nullable',
                Rule::unique('customers')
                    ->where('company_id', $this->header('company'))
                    ->ignore($this->route('customer')->id),
            ];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->customer_type === 'business' && $this->siren && $this->siret && substr($this->siret, 0, 9) !== $this->siren) {
                $validator->errors()->add('siret', 'Le SIRET doit commencer par le SIREN du client.');
            }
        });
    }

    protected function prepareForValidation()
    {
        $customerType = $this->customer_type ?: 'business';
        $businessFields = $customerType === 'business'
            ? [
                'siren' => $this->digitsOnly($this->siren),
                'siret' => $this->digitsOnly($this->siret),
                'vat_number' => $this->upperCompact($this->vat_number),
                'ape_code' => $this->upperCompact($this->ape_code),
            ]
            : [
                'company_name' => null,
                'siren' => null,
                'siret' => null,
                'vat_number' => null,
                'ape_code' => null,
            ];

        $this->merge(array_merge([
            'customer_type' => $customerType,
            'enable_portal' => filter_var($this->enable_portal, FILTER_VALIDATE_BOOLEAN),
        ], $businessFields));
    }

    public function getCustomerPayload()
    {
        return collect($this->validated())
            ->only([
                'name',
                'customer_type',
                'email',
                'electronic_invoicing_email',
                'currency_id',
                'password',
                'phone',
                'prefix',
                'company_name',
                'contact_name',
                'website',
                'siren',
                'siret',
                'vat_number',
                'ape_code',
                'enable_portal',
                'estimate_prefix',
                'payment_prefix',
                'invoice_prefix',
            ])
            ->merge([
                'creator_id' => $this->user()->id,
                'company_id' => $this->header('company'),
            ])
            ->toArray();
    }

    public function getShippingAddress()
    {
        return collect($this->shipping)
            ->merge(['type' => Address::SHIPPING_TYPE])
            ->toArray();
    }

    public function getBillingAddress()
    {
        return collect($this->billing)
            ->merge(['type' => Address::BILLING_TYPE])
            ->toArray();
    }

    public function hasAddress(array $address)
    {
        return Arr::where($address, function ($value) {
            return isset($value);
        });
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
