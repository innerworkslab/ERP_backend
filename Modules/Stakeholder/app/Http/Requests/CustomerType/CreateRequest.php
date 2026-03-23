<?php

namespace Modules\Stakeholder\app\Http\Requests\CustomerType;

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
            'name' => ['required', 'string', 'max:100', 'unique:customer_types,name'],
        ];
    }
}
