<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Le motif de l’avoir est obligatoire.',
            'reason.min' => 'Le motif de l’avoir doit contenir au moins trois caractères.',
            'reason.max' => 'Le motif de l’avoir ne peut pas dépasser 2 000 caractères.',
            'amount.integer' => 'Le montant de l’avoir doit être exprimé en centimes entiers.',
            'amount.min' => 'Le montant de l’avoir doit être supérieur à zéro.',
        ];
    }
}
