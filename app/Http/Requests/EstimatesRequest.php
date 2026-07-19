<?php

namespace Crater\Http\Requests;

use Crater\Models\CompanySetting;
use Crater\Models\Customer;
use Crater\Models\Estimate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimatesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'estimate_date' => ['required'],
            'expiry_date' => ['nullable'],
            'customer_id' => ['required'],
            'estimate_number' => [
                'required',
                Rule::unique('estimates')->where('company_id', $this->header('company')),
            ],
            'exchange_rate' => ['nullable'],
            'discount' => ['required'],
            'discount_val' => ['required'],
            'sub_total' => ['required'],
            'total' => ['required'],
            'tax' => ['required'],
            'template_name' => ['required'],
            'asset_draft_token' => ['nullable', 'uuid'],
            'annex_title' => ['nullable', 'string', 'max:255'],
            'annex_notes' => ['nullable', 'string', 'max:20000'],
            'include_photo_annex' => ['sometimes', 'boolean'],
            'items' => ['required', 'array'],
            'items.*.description' => ['nullable'],
            'items.*.name' => ['required'],
            'items.*.line_uuid' => ['nullable', 'uuid'],
            'items.*.quantity' => ['required'],
            'items.*.price' => ['required'],
        ];

        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));
        $customer = Customer::find($this->customer_id);

        if ($companyCurrency && $customer && (string) $customer->currency_id !== $companyCurrency) {
            $rules['exchange_rate'] = ['required'];
        }

        if ($this->isMethod('PUT')) {
            $rules['estimate_number'] = [
                'required',
                Rule::unique('estimates')
                    ->ignore($this->route('estimate')->id)
                    ->where('company_id', $this->header('company')),
            ];
        }

        return $rules;
    }

    public function getEstimatePayload()
    {
        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));
        $currentCurrency = $this->currency_id;
        $exchangeRate = $companyCurrency != $currentCurrency ? $this->exchange_rate : 1;
        $currency = Customer::find($this->customer_id)->currency_id;

        return collect($this->except('items', 'taxes', 'asset_draft_token', 'attachments'))
            ->merge([
                'creator_id' => $this->user()->id ?? null,
                'status' => $this->has('estimateSend') ? Estimate::STATUS_SENT : Estimate::STATUS_DRAFT,
                'company_id' => $this->header('company'),
                'tax_per_item' => trim((string) (CompanySetting::getSetting('tax_per_item', $this->header('company')) ?? 'NO')),
                'discount_per_item' => CompanySetting::getSetting('discount_per_item', $this->header('company')) ?? 'NO',
                'exchange_rate' => $exchangeRate,
                'base_discount_val' => $this->discount_val * $exchangeRate,
                'base_sub_total' => $this->sub_total * $exchangeRate,
                'base_total' => $this->total * $exchangeRate,
                'base_tax' => $this->tax * $exchangeRate,
                'currency_id' => $currency,
            ])
            ->toArray();
    }
}
