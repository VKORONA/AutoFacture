<?php

namespace Crater\Http\Requests;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'unit_id' => ['nullable'],
            'description' => ['nullable', 'string', 'max:65000'],
            'business_activity_type' => [
                'required',
                Rule::in(BusinessActivityType::values()),
            ],
        ];
    }
}
