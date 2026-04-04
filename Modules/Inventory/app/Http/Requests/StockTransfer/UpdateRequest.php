<?php

namespace Modules\Inventory\app\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $lines = $this->input('lines', []);

        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as &$line) {
            if (!array_key_exists('uom_id', $line) && array_key_exists('unit_id', $line)) {
                $line['uom_id'] = $line['unit_id'];
            }
        }

        $this->merge(['lines' => $lines]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'transfer_date' => 'required|date',
            'source_inventory_id' => 'required|integer|exists:inventories,id|different:target_inventory_id',
            'target_inventory_id' => 'required|integer|exists:inventories,id|different:source_inventory_id',
            'status' => 'nullable|in:pending,confirmed,rejected',
            'remarks' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.uom_id' => 'required|integer|exists:unit_of_measurements,id',
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
