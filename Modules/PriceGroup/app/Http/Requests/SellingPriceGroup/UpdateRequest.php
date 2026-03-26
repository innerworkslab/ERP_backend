<?php

namespace Modules\PriceGroup\app\Http\Requests\SellingPriceGroup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('selling_price_groups', 'name')->ignore($id),
            ],
            'customer_type_id' => ['sometimes', 'nullable', 'integer', 'exists:customer_types,id'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'profit_margin_type' => ['sometimes', 'required', 'in:percentage,fixed'],
            'profit_margin_value' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                Rule::when($this->input('profit_margin_type') === 'percentage', ['lte:100']),
            ],
        ];
    }
}
