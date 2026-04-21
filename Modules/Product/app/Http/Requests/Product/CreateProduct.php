<?php

namespace Modules\Product\app\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProduct extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'alert_quantity' => 'nullable|numeric|min:0',
            'stock_uom_id' => 'nullable|integer|exists:unit_of_measurements,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_currency_id' => 'required|integer|exists:currencies,id',
            'purchase_tax_id' => 'nullable|integer|exists:taxs,id',
            'purchase_uom_id' => 'nullable|integer|exists:unit_of_measurements,id',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_currency_id' => 'required|integer|exists:currencies,id',
            'sale_tax_id' => 'nullable|integer|exists:taxs,id',
            'sale_uom_id' => 'nullable|integer|exists:unit_of_measurements,id',
            'origin_country_id' => 'nullable|integer|exists:origin_countries,id',
            'status' => 'required|in:active,inactive',
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
