<?php

namespace Modules\Accounting\app\Http\Requests\CashbookTransaction;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'search'=> 'string',
            'status' => 'string|in:pending,confirmed,cancelled',
            'cashbook_id' => 'integer|exists:cashbooks,id',
            'category' => 'string|in:expense,income',
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
