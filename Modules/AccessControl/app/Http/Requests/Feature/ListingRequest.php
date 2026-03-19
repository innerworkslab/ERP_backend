<?php

namespace Modules\AccessControl\app\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'integer',
            'per_page' => 'integer',
            'search'=> 'string',
            'role_id' => 'integer|exists:roles,id',
            'branch_id' => 'integer|exists:branches,id',
            'department_id' => 'integer|exists:departments,id',
            'status' => 'string|in:active,inactive'
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
