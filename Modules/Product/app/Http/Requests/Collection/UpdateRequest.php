<?php

namespace Modules\Product\app\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|string|max:255|unique:collections,name,' . $id,
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_currency_id' => 'nullable|integer|exists:currencies,id',
            'purchase_tax_id' => 'nullable|integer|exists:taxs,id',
            'purchase_uom_id' => 'nullable|integer|exists:unit_of_measurements,id',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_currency_id' => 'nullable|integer|exists:currencies,id',
            'sale_tax_id' => 'nullable|integer|exists:taxs,id',
            'sale_uom_id' => 'nullable|integer|exists:unit_of_measurements,id',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
