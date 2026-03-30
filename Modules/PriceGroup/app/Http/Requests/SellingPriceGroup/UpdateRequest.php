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
        ];
    }
}
