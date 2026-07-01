<?php

namespace Modules\Inventory\app\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'goods_receive_note_id' => ['required', 'integer', 'exists:goods_receive_notes,id'],
            'return_date' => ['required', 'date'],
            'return_type' => ['required', 'in:exchange,fully_returned'],
            'exchange_type' => ['required_if:return_type,exchange', 'in:full,partial'],
            'remarks' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.goods_receive_note_line_id' => ['required', 'integer', 'exists:goods_receive_notes_lines,id'],
            'lines.*.return_quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.reason' => ['nullable', 'string'],
        ];
    }
    
    public function authorize(): bool
    {
        return true;
    }
}
