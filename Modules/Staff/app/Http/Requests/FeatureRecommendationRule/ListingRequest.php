<?php

namespace Modules\Staff\app\Http\Requests\FeatureRecommendationRule;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
            'role_id' => 'nullable|integer|exists:roles,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'feature_id' => 'nullable|integer|exists:features,id',
            'status' => 'nullable|in:active,inactive',
            'is_default_recommended' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        return $validator->errors();
    }
}
