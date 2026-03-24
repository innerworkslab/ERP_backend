<?php

namespace Modules\PriceGroup\app\Http\Requests\DiscountGroup;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:discount_groups,name'],
            'customer_type_id' => ['nullable', 'integer', 'exists:customer_types,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }
}
