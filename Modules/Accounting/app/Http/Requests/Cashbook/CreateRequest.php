<?php

namespace Modules\Accounting\app\Http\Requests\Cashbook;

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
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                /**
                 * Prevent duplicate name in same branch
                 */
                Rule::unique('cashbooks', 'name')
                    ->where(function ($query) {
                        return $query->where('branch_id', $this->branch_id);
                    }),
            ],

            'type' => [
                'required',
                Rule::in([
                    'cash',
                    'bank',
                    'mobile_wallet',
                    'petty_cash',
                ]),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'remark' => [
                'nullable',
                'string',
            ],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
