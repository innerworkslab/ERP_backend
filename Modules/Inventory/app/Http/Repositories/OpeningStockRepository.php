<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Models\OpeningStock;
use Modules\Inventory\app\Models\OpeningStockLine;

class OpeningStockRepository extends BaseRepo
{
    protected $opening_stock_line;

    public function __construct(OpeningStock $model, OpeningStockLine $opening_stock_line)
    {
        parent::__construct($model);
        $this->opening_stock_line = $opening_stock_line;
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'inventory',
                'lines.product',
                'lines.unit',
                'created_by',
                'updated_by',
                'confirmed_by',
            ]);
        }

        return $data;
    }

    public function create(array $attributes)
    {
        $openingStock = $this->model->create([
            'voucher_no' => $attributes['voucher_no'],
            'voucher_date' => $attributes['voucher_date'],
            'inventory_id' => $attributes['inventory_id'],
            'total_amount' => $attributes['total_amount'],
            'status' => $attributes['status'] ?? 'pending',
            'remarks' => $attributes['remarks'] ?? null,
            'created_by' => $attributes['created_by'],
        ]);

        $lines = $attributes['lines'] ?? [];

        foreach ($lines as $line) {
            $this->opening_stock_line->create([
                'opening_stock_id' => $openingStock->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'uom_id' => $line['uom_id'],
                'purchase_price' => $line['purchase_price'],
                'subtotal' => $line['subtotal'],
                'lot_no' => $line['lot_no'] ?? null,
                'expired_date' => $line['expired_date'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'remarks' => $line['remarks'] ?? null,
            ]);
        }

        return $openingStock;
    }

    public function updateWithLines(int $id, array $attributes)
    {
        $openingStock = $this->find($id);

        if (!$openingStock) {
            return null;
        }

        $openingStock->update([
            'inventory_id' => $attributes['inventory_id'],
            'total_amount' => $attributes['total_amount'],
            'status' => $attributes['status'] ?? 'pending',
            'remarks' => $attributes['remarks'] ?? null,
            'updated_by' => $attributes['updated_by'],
        ]);

        $this->opening_stock_line->where('opening_stock_id', $openingStock->id)->delete();

        foreach ($attributes['lines'] as $line) {
            $this->opening_stock_line->create([
                'opening_stock_id' => $openingStock->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'uom_id' => $line['uom_id'],
                'purchase_price' => $line['purchase_price'],
                'subtotal' => $line['subtotal'],
                'lot_no' => $line['lot_no'] ?? null,
                'expired_date' => $line['expired_date'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
                'remarks' => $line['remarks'] ?? null,
            ]);
        }

        return $openingStock;
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
    }
}
