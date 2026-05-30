<?php

namespace Modules\Inventory\app\Http\Requests\GoodsReceiveNote;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'inventory_id' => ['required', 'integer', 'exists:inventories,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'grn_date' => ['required', 'date'],
            'fee_allocation_method' => ['nullable', 'in:by_weight,by_line_value,equal_qty,equal_line'],
            'tax_allocation_method' => ['nullable', 'in:by_weight,by_products'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'cargo_tax_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],

            'charges' => ['nullable', 'array'],
            'charges.*.charge_type' => ['required_with:charges', 'in:cargo,delivery,other'],
            'charges.*.currency_id' => ['required_with:charges', 'integer', 'exists:currencies,id'],
            'charges.*.amount' => ['required_with:charges', 'numeric', 'min:0'],
            'charges.*.description' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', 'exists:purchase_order_lines,id'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.uom_id' => ['nullable', 'integer', 'exists:unit_of_measurements,id'],
            'lines.*.ordered_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'lines.*.previously_received_quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.good_quantity' => ['required', 'numeric', 'min:0', 'lte:lines.*.received_quantity'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.line_weight' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discrepancy_reason' => ['nullable', 'in:none,cashback,defect'],
            'lines.*.defect_responsibility' => ['nullable', 'in:supplier_side,company_side', 'required_if:lines.*.discrepancy_reason,defect'],
            'lines.*.remarks' => ['nullable', 'string'],
            'lines.*.manual_tax_amount' => ['nullable', 'numeric', 'min:0', 'required_if:tax_allocation_method,by_products'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
