<?php

namespace Modules\Staff\app\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        return $errors;
    }
}
