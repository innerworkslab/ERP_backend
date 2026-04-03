<?php

namespace Modules\Inventory\app\Http\Requests\OpeningStock;

use Illuminate\Foundation\Http\FormRequest;

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
     */
    public function rules(): array
    {
        return [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'search' => 'nullable|string',
            'voucher_no' => 'nullable|string',
            'status' => 'string|in:pending,confirmed',
            'inventory_id' => 'integer|exists:inventories,id',
            'inventory' => 'nullable|string',
            'voucher_date' => 'nullable|date',
            'voucher_date_from' => 'date',
            'voucher_date_to' => 'date|after_or_equal:voucher_date_from',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
