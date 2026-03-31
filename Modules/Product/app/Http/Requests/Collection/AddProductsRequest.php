<?php

namespace Modules\Product\app\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;

class AddProductsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|distinct|exists:products,id',
            'products.*.product_qty' => 'required|numeric|gt:0',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}