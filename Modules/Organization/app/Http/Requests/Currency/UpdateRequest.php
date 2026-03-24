<?php

namespace Modules\Organization\app\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|string|max:100|unique:currencies,name,' . $id,
            'code' => 'required|string|max:10|unique:currencies,code,' . $id,
            'symbol' => 'nullable|string|max:10',
            'exchange_rate' => 'required|numeric|gt:0',
            'is_base_currency' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
