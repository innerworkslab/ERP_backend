<?php

namespace Modules\Accounting\app\Http\Requests\CashbookTransaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
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

            'cashbook_id' => [
                'required',
                'integer',
                'exists:cashbooks,id',
            ],

            'source_account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],

            'destination_account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
                'different:source_account_id',
            ],

            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],

            'transaction_type' => [
                'required',
                Rule::in([
                    'in',
                    'out',
                ]),
            ],

            'category' => [
                'required',
                Rule::in([
                    'expense',
                    'income',
                    'transfer',
                    'adjustment',
                    'deposit',
                    'withdraw',
                    'others',
                ]),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            // 'base_currency_amount' => [
            //     'required',
            //     'numeric',
            //     'gt:0',
            // ],


            // 'reference_no' => [
            //     'nullable',
            //     'string',
            //     'max:255',
            // ],

            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'attachments' => [
                'nullable',
                'array',
            ],

            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'amount.gt' => 'Amount must be greater than zero.',

            // 'base_currency_amount.gt' =>
            //     'Base currency amount must be greater than zero.',

            'attachments.*.mimes' =>
                'Attachment must be image, pdf, excel, csv, or document file.',

            'attachments.*.max' =>
                'Each attachment must not exceed 10MB.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
