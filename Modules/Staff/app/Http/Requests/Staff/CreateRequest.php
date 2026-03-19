<?php

namespace Modules\Staff\app\Http\Requests\Staff;

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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:50|unique:users,phone_number',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive',

            'personal_information' => 'required|array',
            'personal_information.date_of_birth' => 'nullable|date',
            'personal_information.nrc_number' => 'nullable|string|max:50|unique:staff_personal_informations,nrc_number',
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
            'employment_information.salary' => 'required|numeric|min:0',
            'employment_information.sale_incentive_amount' => 'required|numeric|min:0',
            'employment_information.sale_commission' => 'nullable|numeric|min:0',

            'banking_information' => 'nullable|array',
            'banking_information.bank_name' => 'nullable|string|max:100',
            'banking_information.account_number' => 'nullable|string|max:100',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();

        return $errors;
    }
}
