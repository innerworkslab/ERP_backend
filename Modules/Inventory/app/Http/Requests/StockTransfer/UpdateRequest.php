<?php

namespace Modules\Inventory\app\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\app\Http\Services\UOMConversionService;
use Modules\Product\app\Models\Product;

class UpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $lines = $this->input('lines', []);

        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as &$line) {
            if (!array_key_exists('uom_id', $line) && array_key_exists('unit_id', $line)) {
                $line['uom_id'] = $line['unit_id'];
            }
        }

        $this->merge(['lines' => $lines]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'transfer_date' => 'required|date',
            'source_inventory_id' => 'required|integer|exists:inventories,id|different:target_inventory_id',
            'target_inventory_id' => 'required|integer|exists:inventories,id|different:source_inventory_id',
            'status' => 'nullable|in:pending,confirmed,rejected',
            'remarks' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.lot_no' => 'required|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.uom_id' => 'required|integer|exists:unit_of_measurements,id',
            'lines.*.remarks' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'lines.*.uom_id.uom_conversion_exists' => 'The UOM for product :attribute does not match stock UOM and no conversion rule is defined.',
        ];
    }

    /**
     * Configure the validator instance with custom validation rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            $uomConversionService = new UOMConversionService();

            foreach ($lines as $index => $line) {
                if (!isset($line['product_id'], $line['uom_id'])) {
                    continue;
                }

                $product = Product::find($line['product_id']);
                if (!$product || !$product->stock_uom_id) {
                    $validator->errors()->add(
                        "lines.{$index}.product_id",
                        "Product does not have a stock UOM configured."
                    );
                    continue;
                }

                $transferUomId = (int) $line['uom_id'];
                $stockUomId = (int) $product->stock_uom_id;

                // Only check conversion rule if UOM is different from product's stock UOM
                if ($transferUomId !== $stockUomId) {
                    if (!$uomConversionService->hasConversionRule($stockUomId, $transferUomId)) {
                        $validator->errors()->add(
                            "lines.{$index}.uom_id",
                            "No conversion rule found between the product's stock UOM and the specified UOM."
                        );
                    }
                }
            }
        });

        return $validator;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors();
        return $errors;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
