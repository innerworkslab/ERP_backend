<?php

namespace Modules\Accounting\app\Http\Requests\CashbookTransaction;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],

            'category' => [
                'required',
                'in:expense,income',
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

            'existing_attachment_ids' => [
                'nullable',
                'array',
            ],

            'existing_attachment_ids.*' => [
                'integer',
                'exists:cashbook_transaction_attachments,id'
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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'response' => [
                'status' => 'error',
                'message' => 'Validation error',
            ],
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
