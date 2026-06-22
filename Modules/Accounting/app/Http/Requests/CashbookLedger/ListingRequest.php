<?php

namespace Modules\Accounting\app\Http\Requests\CashbookLedger;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'search' => 'string',
            'cashbook_id' => 'nullable|integer|exists:cashbooks,id',
            'cashbook_transaction_id' => 'integer|exists:cashbook_transactions,id',
            'transaction_type' => [
                'string',
                Rule::in(['in', 'out']),
            ],
            'date' => 'nullable|date',
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        return $validator->errors();
    }
}
