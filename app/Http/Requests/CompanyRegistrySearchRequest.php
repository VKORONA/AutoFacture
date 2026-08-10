<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRegistrySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'postal_code' => ['nullable', 'regex:/^\d{5}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'exclude_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = trim((string) $this->input('q'));
        $digits = preg_replace('/\D+/', '', $query);

        $this->merge([
            'q' => in_array(strlen($digits), [9, 14], true) ? $digits : $query,
            'postal_code' => filled($this->input('postal_code'))
                ? preg_replace('/\D+/', '', (string) $this->input('postal_code'))
                : null,
            'city' => filled($this->input('city')) ? trim((string) $this->input('city')) : null,
        ]);
    }
}
