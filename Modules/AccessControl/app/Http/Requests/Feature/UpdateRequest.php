<?php

namespace Modules\AccessControl\app\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        $id = $this->route("id");
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')
                    ->ignore($id)
                    ->where(function ($query) {
                        return $query->where('branch_id', $this->branch_id)
                                     ->where('department_id', $this->department_id);
                    }),
            ],

            'status' => 'required|in:active,inactive',

            'parent_role_id' => 'nullable|integer|exists:roles,id',

            'branch_id' => 'required|integer|exists:branches,id',

            'department_id' => 'required|integer|exists:departments,id',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
