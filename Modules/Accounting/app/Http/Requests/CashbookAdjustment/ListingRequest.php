<?php

namespace Modules\Accounting\app\Http\Requests\CashbookAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cashbook_id' => 'nullable|exists:cashbooks,id',
            'type' => 'nullable|in:increase,decrease',
            'status' => 'nullable|in:pending,approved,rejected',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
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
