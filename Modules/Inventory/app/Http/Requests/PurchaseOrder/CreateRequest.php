<?php

namespace Modules\Inventory\app\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'po_date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'inventory_id' => ['required', 'integer', 'exists:inventories,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.uom_id' => ['required', 'integer', 'exists:unit_of_measurements,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', 'integer', 'exists:taxs,id'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.expenses_type' => ['nullable', 'in:none,freight,packing,insurance,handling,other'],
            'lines.*.expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
