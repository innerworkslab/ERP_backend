<?php

namespace Modules\Inventory\app\Http\Requests\PurchaseOrder;

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
            'po_number' => 'nullable|string',
            'status' => 'nullable|string|in:pending,draft,ordered,cancelled',
            'payment_status' => 'nullable|string|in:unpaid,partially_paid,paid',
            'delivery_status' => 'nullable|string|in:not_delivered,partially_delivered,fully_delivered',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'inventory_id' => 'nullable|integer|exists:inventories,id',
            'po_date' => 'nullable|date',
            'po_date_from' => 'nullable|date',
            'po_date_to' => 'nullable|date|after_or_equal:po_date_from',
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
