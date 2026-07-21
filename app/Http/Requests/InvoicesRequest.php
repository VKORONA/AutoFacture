<?php

namespace Crater\Http\Requests;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Crater\Models\CompanySetting;
use Crater\Models\Customer;
use Crater\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = (int) $this->header('company');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(
                    static fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'invoice_number' => $isUpdate
                ? [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('invoices')
                        ->ignore($this->route('invoice')->id)
                        ->where('company_id', $companyId),
                ]
                : ['nullable', 'string', 'max:255'],
            'client_request_id' => ['nullable', 'uuid'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'discount_val' => ['required', 'integer', 'min:0'],
            'sub_total' => ['nullable', 'integer', 'min:0'],
            'total' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer'],
            'template_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'project_name' => ['nullable', 'string', 'max:255'],
            'project_address' => ['nullable', 'string', 'max:500'],
            'purchase_order_number' => ['nullable', 'string', 'max:100'],
            'project_contact' => ['nullable', 'string', 'max:255'],
            'payment_terms_label' => ['nullable', 'string', 'max:255'],
            'show_sepa_qr' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.business_activity_type' => [
                'required',
                Rule::in(BusinessActivityType::values()),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.discount_val' => ['nullable', 'integer', 'min:0'],
            'items.*.tax' => ['nullable', 'integer'],
            'items.*.total' => ['required', 'integer', 'min:0'],
            'taxes' => ['nullable', 'array'],
            'taxes.*.amount' => ['nullable', 'integer'],
        ];

        $companyCurrency = CompanySetting::getSetting('currency', $companyId);
        $customer = Customer::query()
            ->whereKey((int) $this->input('customer_id'))
            ->where('company_id', $companyId)
            ->first();

        if ($customer && $companyCurrency && (string) $customer->currency_id !== (string) $companyCurrency) {
            $rules['exchange_rate'] = ['required', 'numeric', 'gt:0'];
        }

        return $rules;
    }

    public function getInvoicePayload(): array
    {
        $customer = Customer::findOrFail((int) $this->input('customer_id'));
        $companyId = (int) ($this->header('company') ?: $customer->company_id);
        $companyCurrency = CompanySetting::getSetting('currency', $companyId);

        $currentCurrency = $this->input('currency_id');
        $exchangeRate = (string) $companyCurrency !== (string) $currentCurrency
            ? (float) $this->input('exchange_rate')
            : 1.0;

        $excluded = ['items', 'taxes'];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $excluded[] = 'client_request_id';
        }

        return collect($this->except($excluded))
            ->merge([
                'creator_id' => $this->user()->id ?? null,
                'status' => $this->has('invoiceSend') ? Invoice::STATUS_SENT : Invoice::STATUS_DRAFT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'company_id' => $companyId,
                'tax_per_item' => trim((string) (CompanySetting::getSetting('tax_per_item', $companyId) ?? 'NO')),
                'discount_per_item' => trim((string) (CompanySetting::getSetting('discount_per_item', $companyId) ?? 'NO')),
                'due_amount' => (int) $this->input('total'),
                'exchange_rate' => $exchangeRate,
                'base_total' => (int) round((float) $this->input('total') * $exchangeRate),
                'base_discount_val' => (int) round((float) $this->input('discount_val') * $exchangeRate),
                'base_sub_total' => (int) round((float) $this->input('sub_total') * $exchangeRate),
                'base_tax' => (int) round((float) $this->input('tax') * $exchangeRate),
                'base_due_amount' => (int) round((float) $this->input('total') * $exchangeRate),
                'currency_id' => $customer->currency_id,
                'show_sepa_qr' => $this->boolean('show_sepa_qr', true),
            ])
            ->toArray();
    }
}
