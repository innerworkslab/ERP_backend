<?php

namespace Modules\Sale\app\Http\Requests\SaleInvoice;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'inventory_id' => ['required', 'integer', 'exists:inventories,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'selling_price_group_id' => ['required', 'integer', 'exists:selling_price_groups,id'],
            'inventory_transaction_method' => ['nullable', 'in:FIFO,LIFO,custom_batch'],
            'invoice_date' => ['required', 'date'],
            'payment_terms' => ['required', 'in:net30,net60,due_on_receipt,advanced_payment,cash_on_delivery'],
            'payment_due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,pending'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'sell_tax_id' => ['nullable', 'integer', 'exists:taxs,id'],
            'invoice_discount_type' => ['nullable', 'in:fixed,percentage'],
            'invoice_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'cashbook_id' => ['nullable', 'integer', 'exists:cashbooks,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.uom_id' => ['nullable', 'integer', 'exists:unit_of_measurements,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:fixed,percentage'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
            'delivery' => ['nullable', 'array'],
            'delivery.delivery_provider_id' => ['nullable', 'integer', 'exists:delivery_providers,id'],
            'delivery.delivery_charge_paid' => ['nullable', 'in:shipper,receiver'],
            'delivery.delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'delivery.receiver_name' => ['nullable', 'string', 'max:255', 'required_with:delivery.delivery_provider_id'],
            'delivery.receiver_phone' => ['nullable', 'string', 'max:50', 'required_with:delivery.delivery_provider_id'],
            'delivery.receiver_address' => ['nullable', 'string', 'max:255', 'required_with:delivery.delivery_provider_id'],
            'delivery.receiver_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
