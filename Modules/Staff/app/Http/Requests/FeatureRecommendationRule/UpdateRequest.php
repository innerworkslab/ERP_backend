<?php

namespace Modules\Staff\app\Http\Requests\FeatureRecommendationRule;

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
        $id = $this->route('id');

        return [
            'role_id' => 'required|integer|exists:roles,id',
            'department_id' => 'required|integer|exists:departments,id',
            'feature_id' => [
                'required',
                'integer',
                'exists:features,id',
                Rule::unique('staff_feature_recommendation_rules')
                    ->ignore($id)
                    ->where(function ($query) {
                        return $query->where('role_id', $this->role_id);
                    }),
            ],
            'is_default_recommended' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        return $validator->errors();
    }
}
