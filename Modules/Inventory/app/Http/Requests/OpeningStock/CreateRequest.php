<?php

namespace Modules\Inventory\app\Http\Requests\OpeningStock;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'inventory_id' => 'required|integer|exists:inventories,id',
            'status' => 'nullable|in:pending,confirmed',
            'remarks' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.uom_id' => 'required|integer|exists:unit_of_measurements,id',
            'lines.*.purchase_price' => 'required|numeric|min:0',
            'lines.*.subtotal' => 'required|numeric|min:0',
            'lines.*.lot_no' => 'nullable|string',
            'lines.*.expired_date' => 'nullable|date',
            'lines.*.serial_no' => 'nullable|string',
            'lines.*.remarks' => 'nullable|string',
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
