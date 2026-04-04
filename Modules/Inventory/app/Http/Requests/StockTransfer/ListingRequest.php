<?php

namespace Modules\Inventory\app\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'search' => 'nullable|string',
            'reference_id' => 'nullable|string',
            'status' => 'nullable|string|in:pending,confirmed,rejected',
            'source_inventory_id' => 'nullable|integer|exists:inventories,id',
            'target_inventory_id' => 'nullable|integer|exists:inventories,id',
            'source_inventory' => 'nullable|string',
            'target_inventory' => 'nullable|string',
            'transfer_date' => 'nullable|date',
            'transfer_date_from' => 'nullable|date',
            'transfer_date_to' => 'nullable|date|after_or_equal:transfer_date_from',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
