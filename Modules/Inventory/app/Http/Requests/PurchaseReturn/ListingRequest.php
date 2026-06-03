<?php

namespace Modules\Inventory\app\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'return_no' => ['nullable', 'string'],
            'goods_receive_note_id' => ['nullable', 'integer', 'exists:goods_receive_notes,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'return_type' => ['nullable', 'in:exchange,fully_returned'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'return_date_from' => ['nullable', 'date'],
            'return_date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
