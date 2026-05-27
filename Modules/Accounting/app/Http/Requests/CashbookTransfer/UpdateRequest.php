<?php

namespace Modules\Accounting\app\Http\Requests\CashbookTransfer;

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
            'source_cashbook_id' => ['required', 'integer', 'exists:cashbooks,id'],
            'destination_cashbook_id' => ['required', 'integer', 'exists:cashbooks,id', 'different:source_cashbook_id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Amount must be greater than zero.',
        ];
    }
}
