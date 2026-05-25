<?php

namespace Modules\Inventory\app\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cashbook_id' => ['nullable', 'integer', 'exists:cashbooks,id'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
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
