<?php

namespace Modules\Sale\app\Http\Requests\DeliveryProvider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:delivery_providers,name,' . $this->route('id'),
            'default_price' => 'required|numeric|min:0',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
