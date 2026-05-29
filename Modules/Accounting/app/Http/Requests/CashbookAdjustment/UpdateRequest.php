<?php

namespace Modules\Accounting\app\Http\Requests\CashbookAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cashbook_id' => 'required|exists:cashbooks,id',
            'type' => 'required|in:increase,decrease',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
