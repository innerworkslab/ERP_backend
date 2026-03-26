<?php

namespace Modules\PriceGroup\app\Http\Requests\SellingPriceGroup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:selling_price_groups,name'],
            'customer_type_id' => ['nullable', 'integer', 'exists:customer_types,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'profit_margin_type' => ['required', 'in:percentage,fixed'],
            'profit_margin_value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->input('profit_margin_type') === 'percentage', ['lte:100']),
            ],
        ];
    }
}
