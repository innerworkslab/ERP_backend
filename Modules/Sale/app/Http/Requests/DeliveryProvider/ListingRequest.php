<?php

namespace Modules\Sale\app\Http\Requests\DeliveryProvider;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
