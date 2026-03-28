<?php

namespace Modules\Organization\app\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
        return [
            'prefix' => 'required|string|max:50|unique:branches,prefix',
            'name' => 'required|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'state_id' => 'required|integer|exists:states,id',
            'mobile' => 'required|string',
            'alternate_phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|string',
            'default_selling_price_group_id' => 'required|integer|exists:selling_price_groups,id',
            'status' => 'required|in:active,inactive',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }
}
