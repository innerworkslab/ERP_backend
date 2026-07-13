<?php

namespace Modules\Sale\app\Http\Requests\DeliverNote;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sale_invoice_id' => ['required', 'integer', 'exists:sale_invoices,id'],
            'source_inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_provider_id' => ['nullable', 'integer', 'exists:delivery_providers,id'],
            'receiver_name' => ['nullable', 'string', 'max:255', 'required_with:delivery_provider_id'],
            'receiver_phone' => ['nullable', 'string', 'max:50', 'required_with:delivery_provider_id'],
            'receiver_address' => ['nullable', 'string', 'max:255', 'required_with:delivery_provider_id'],
            'delivery_note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_invoice_item_id' => ['required', 'integer', 'distinct', 'exists:sale_invoice_items,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
