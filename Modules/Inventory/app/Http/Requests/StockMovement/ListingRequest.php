<?php

namespace Modules\Inventory\app\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'voucher_no' => 'nullable|string|max:255',
            'reference_type' => 'nullable|string|in:opening_stock,purchase_receive,purchase_return,sale_issue,sale_return,adjustment_in,adjustment_out,damage,transfer_in,transfer_out',
            'reference_id' => 'nullable|integer',
            'product_search' => 'nullable|string|max:255',
            'lot_no' => 'nullable|string|max:255',
            'inventory_id' => 'nullable|integer',
            'transaction_date_from' => 'nullable|date',
            'transaction_date_to' => 'nullable|date',
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
