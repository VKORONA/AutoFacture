<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
