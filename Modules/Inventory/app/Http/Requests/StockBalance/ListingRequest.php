<?php

namespace Modules\Inventory\app\Http\Requests\StockBalance;

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
            'search' => 'nullable|string|max:255',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'inventory_id' => 'nullable|integer|exists:inventories,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'collection_id' => 'nullable|integer|exists:collections,id',
            'lot_no' => 'nullable|string|max:255',
            'expired_date_from' => 'nullable|date',
            'expired_date_to' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
            'expiry_status' => 'nullable|in:expired,nearly_expired,fresh',
            'near_expiry_days' => 'nullable|integer|min:1|max:365',
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
