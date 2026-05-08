<?php

namespace Modules\Stakeholder\app\Http\Requests\Customer;

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
            'state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'address' => ['sometimes', 'nullable', 'string'],
            'bank_account_id' => ['sometimes', 'nullable', 'integer', 'exists:customer_bank_accounts,id'],
            'bank_accounts' => ['sometimes', 'nullable', 'array', 'min:1'],
            'bank_accounts.*.bank_name' => ['required_with:bank_accounts', 'string', 'max:150'],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts', 'string', 'max:100'],
            'bank_accounts.*.holder_name' => ['required_with:bank_accounts', 'string', 'max:150'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'branch_ids' => ['sometimes', 'nullable', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'integer', 'distinct', 'exists:branches,id'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'opening' => ['sometimes', 'nullable', 'numeric'],
            'customer_type_id' => ['sometimes', 'required', 'integer', 'exists:customer_types,id'],
            'birthday' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'nullable', 'in:Active,Inactive'],
        ];
    }
}
