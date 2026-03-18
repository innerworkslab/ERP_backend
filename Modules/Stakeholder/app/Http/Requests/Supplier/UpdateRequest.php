<?php

namespace Modules\Stakeholder\app\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'town' => ['sometimes', 'nullable', 'string', 'max:100'],
            'township' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string'],
            'bank_acc' => ['sometimes', 'nullable', 'string', 'max:100'],
            'branch_id' => ['sometimes', 'nullable', 'integer'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'opening' => ['sometimes', 'nullable', 'numeric'],
            'type' => ['sometimes', 'nullable', 'in:Retail,Wholesale'],
            'birthday' => ['sometimes', 'nullable', 'date'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:100'],
            'payment_due' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', 'in:Active,Inactive'],
            'created_by' => ['sometimes', 'nullable', 'integer'],
            'updated_by' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}