<?php

namespace Modules\Product\app\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'category' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:taxs,code,' . $id,
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
