<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Models\GoodsReturnLine;
use Modules\Inventory\app\Models\PurchaseReturn;

class PurchaseReturnRepository extends BaseRepo
{
    public function __construct(
        PurchaseReturn $model,
        protected GoodsReturnLine $lineModel
    ) {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'goodsReceiveNote',
                'purchaseOrder',
                'supplier',
                'branch',
                'inventory',
                'currency',
                'exchangeGoodsReceiveNote',
                'lines.product',
                'lines.uom',
                'lines.tax',
                'lines.goodsReceiveNoteLine',
                'createdBy',
                'approvedBy',
            ]);
        }

        return $data;
    }

    public function createWithLines(array $attributes)
    {
        $purchaseReturn = $this->model->create($this->formatHeader($attributes));

        foreach ($attributes['lines'] ?? [] as $line) {
            $this->lineModel->create($line + ['purchase_return_id' => $purchaseReturn->id]);
        }

        return $this->find($purchaseReturn->id);
    }

    public function updateWithLines(int $id, array $attributes)
    {
        $purchaseReturn = $this->find($id);
        if (!$purchaseReturn) {
            return null;
        }

        $purchaseReturn->update($this->formatHeader($attributes));
        $this->lineModel->where('purchase_return_id', $purchaseReturn->id)->delete();

        foreach ($attributes['lines'] ?? [] as $line) {
            $this->lineModel->create($line + ['purchase_return_id' => $purchaseReturn->id]);
        }

        return $this->find($purchaseReturn->id);
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
    }

    private function formatHeader(array $attributes): array
    {
        return [
            'return_no' => $attributes['return_no'],
            'goods_receive_note_id' => $attributes['goods_receive_note_id'],
            'purchase_order_id' => $attributes['purchase_order_id'],
            'supplier_id' => $attributes['supplier_id'],
            'branch_id' => $attributes['branch_id'],
            'inventory_id' => $attributes['inventory_id'],
            'currency_id' => $attributes['currency_id'],
            'exchange_goods_receive_note_id' => $attributes['exchange_goods_receive_note_id'] ?? null,
            'return_date' => $attributes['return_date'],
            'return_type' => $attributes['return_type'],
            'subtotal_amount' => $attributes['subtotal_amount'] ?? 0,
            'tax_amount' => $attributes['tax_amount'] ?? 0,
            'total_amount' => $attributes['total_amount'] ?? 0,
            'remarks' => $attributes['remarks'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'created_by' => $attributes['created_by'] ?? null,
            'approved_by' => $attributes['approved_by'] ?? null,
            'approved_at' => $attributes['approved_at'] ?? null,
        ];
    }
}
