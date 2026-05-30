<?php

namespace Modules\Inventory\app\Http\Requests\GoodsReceiveNote;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'grn_no' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'grn_date_from' => ['nullable', 'date'],
            'grn_date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
