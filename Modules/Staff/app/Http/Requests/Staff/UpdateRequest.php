<?php

namespace Modules\Staff\app\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'phone_number' => 'required|string|max:50|unique:users,phone_number,' . $id,
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive',

            'personal_information' => 'required|array',
            'personal_information.date_of_birth' => 'nullable|date',
            'personal_information.nrc_number' => 'nullable|string|max:50|unique:staff_personal_informations,nrc_number,' . $id . ',user_id',
            'personal_information.nrc_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'personal_information.father_name' => 'nullable|string|max:20',
            'personal_information.mother_name' => 'nullable|string|max:20',
            'personal_information.town' => 'nullable|string|max:20',
            'personal_information.township' => 'nullable|string|max:20',
            'personal_information.address' => 'nullable|string|max:255',
            'personal_information.house_hold_information_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',

            'employment_information' => 'nullable|array',
            'employment_information.join_date' => 'nullable|date',
            'employment_information.is_contract' => 'nullable|boolean',
            'employment_information.off_day' => 'nullable|string|max:50',
            'employment_information.overtime_fee_type' => 'nullable|string|max:255',
            'employment_information.salary' => 'sometimes|numeric|min:0',
            'employment_information.sale_incentive_amount' => 'sometimes|numeric|min:0',
            'employment_information.sale_commission' => 'nullable|numeric|min:0',

            'banking_information' => 'nullable|array',
            'banking_information.bank_name' => 'nullable|string|max:100',
            'banking_information.account_number' => 'nullable|string|max:100',

            'authorized_features' => 'nullable|array',
            'authorized_features.*.feature_id' => 'required_with:authorized_features|integer|exists:features,id|distinct',
            'authorized_features.*.recommended_by_rule' => 'nullable|string|max:255',
            'authorized_features.*.access_type' => 'nullable|string|max:100',
            'authorized_features.*.permission_level' => 'nullable|string|max:100',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        return $errors;
    }
}
