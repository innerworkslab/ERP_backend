<?php

namespace Modules\Sale\app\Http\Requests\DeliverNote;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'sale_invoice_id' => ['nullable', 'integer', 'exists:sale_invoices,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'source_inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'delivery_provider_id' => ['nullable', 'integer', 'exists:delivery_providers,id'],
            'delivery_date_from' => ['nullable', 'date'],
            'delivery_date_to' => ['nullable', 'date', 'after_or_equal:delivery_date_from'],
            'status' => ['nullable', 'in:draft,pending,confirmed,rejected,cancelled'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
