<?php

namespace Modules\Product\app\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:taxs,code',
            'type' => 'required|in:sale,purchase',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
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
