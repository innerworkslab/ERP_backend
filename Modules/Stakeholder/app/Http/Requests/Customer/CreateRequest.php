<?php

namespace Modules\Stakeholder\app\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'township' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'bank_acc' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening' => ['nullable', 'numeric'],
            'type' => ['nullable', 'in:Retail,Wholesale'],
            'birthday' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'payment_due' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Active,Inactive'],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
        ];
    }
}