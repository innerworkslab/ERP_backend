<?php

namespace Modules\Stakeholder\app\Http\Requests\Supplier;

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
            'bank_account_id' => ['nullable', 'integer', 'exists:supplier_bank_accounts,id'],
            'bank_accounts' => ['required', 'array', 'min:1'],
            'bank_accounts.*.bank_name' => ['required_with:bank_accounts', 'string', 'max:150'],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts', 'string', 'max:100'],
            'bank_accounts.*.holder_name' => ['required_with:bank_accounts', 'string', 'max:150'],

            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening' => ['nullable', 'numeric'],
            'supplier_type_id' => ['required', 'integer', 'exists:supplier_types,id'],
            'birthday' => ['nullable', 'date'],
            'status' => ['nullable', 'in:Active,Inactive'],
        ];
    }
}
