<?php

namespace Modules\Product\app\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'keyword' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
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
