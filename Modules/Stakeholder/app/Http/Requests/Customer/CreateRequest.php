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
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'address' => ['nullable', 'string'],
            'bank_account_id' => ['nullable', 'integer', 'exists:customer_bank_accounts,id'],
            'bank_accounts' => ['nullable', 'array', 'min:1'],
            'bank_accounts.*.bank_name' => ['required_with:bank_accounts', 'string', 'max:150'],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts', 'string', 'max:100'],
            'bank_accounts.*.holder_name' => ['required_with:bank_accounts', 'string', 'max:150'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'branch_ids' => ['nullable', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'integer', 'distinct', 'exists:branches,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening' => ['nullable', 'numeric'],
            'customer_type_id' => ['required', 'integer', 'exists:customer_types,id'],
            'birthday' => ['nullable', 'date'],
            'status' => ['nullable', 'in:Active,Inactive'],
        ];
    }
}
