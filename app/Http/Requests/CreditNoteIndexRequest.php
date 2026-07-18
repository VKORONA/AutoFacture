<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreditNoteIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'orderByField' => [
                'nullable',
                Rule::in(['issue_date', 'credit_note_number', 'total', 'created_at']),
            ],
            'orderBy' => ['nullable', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
