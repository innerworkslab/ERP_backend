<?php

namespace Modules\Sale\app\Http\Requests\SaleInvoice;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'selling_price_group_id' => ['nullable', 'integer', 'exists:selling_price_groups,id'],
            'status' => ['nullable', 'in:draft,pending,ordered,reserved,delivered'],
            'payment_status' => ['nullable', 'in:paid,unpaid,partial'],
            'cashbook_id' => ['nullable', 'integer', 'exists:cashbooks,id'],
            'delivery_provider_id' => ['nullable', 'integer', 'exists:delivery_providers,id'],
            'invoice_date_from' => ['nullable', 'date'],
            'invoice_date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
