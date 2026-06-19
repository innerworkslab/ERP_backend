<?php

namespace Modules\Inventory\app\Http\Requests\UOMConversion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'conversions_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measurement_conversions', 'conversions_name')->ignore($id),
            ],

            'base_unit_id' => [
                'required',
                'exists:unit_of_measurements,id',
            ],

            'conversion_unit_id' => [
                'required',
                'exists:unit_of_measurements,id',
            ],

            'conversion_rate' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
