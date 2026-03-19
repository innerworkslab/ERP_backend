<?php

namespace Modules\Organization\app\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentsBySelectedBranchRequest extends FormRequest
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
            'search' => 'string',
            'status' => 'string|in:active,inactive',
            'branch_id' => 'required|integer|exists:branches,id',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
