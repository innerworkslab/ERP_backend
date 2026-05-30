<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Models\GoodsReceiveNoteCharge;
use Modules\Inventory\app\Models\GoodsReceiveNotes;
use Modules\Inventory\app\Models\GoodsReceiveNotesLine;

class GoodsReceiveNoteRepository extends BaseRepo
{
    protected $lineModel;
    protected $chargeModel;

    public function __construct(
        GoodsReceiveNotes $model,
        GoodsReceiveNotesLine $lineModel,
        GoodsReceiveNoteCharge $chargeModel
    ) {
        parent::__construct($model);
        $this->lineModel = $lineModel;
        $this->chargeModel = $chargeModel;
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'purchaseOrder',
                'supplier',
                'branch',
                'inventory',
                'currency',
                'lines.product',
                'lines.uom',
                'charges.currency',
                'createdBy',
                'approvedBy',
            ]);
        }

        return $data;
    }

    public function createWithRelations(array $attributes)
    {
        $grn = $this->model->create($this->formatHeader($attributes));

        foreach ($attributes['lines'] ?? [] as $line) {
            $this->lineModel->create($line + ['goods_receive_note_id' => $grn->id]);
        }

        foreach ($attributes['charges'] ?? [] as $charge) {
            $this->chargeModel->create($charge + ['goods_receive_note_id' => $grn->id]);
        }

        return $grn;
    }

    public function updateWithRelations(int $id, array $attributes)
    {
        $grn = $this->find($id);
        if (!$grn) {
            return null;
        }

        $grn->update($this->formatHeader($attributes));
        $this->lineModel->where('goods_receive_note_id', $id)->delete();
        $this->chargeModel->where('goods_receive_note_id', $id)->delete();

        foreach ($attributes['lines'] ?? [] as $line) {
            $this->lineModel->create($line + ['goods_receive_note_id' => $grn->id]);
        }

        foreach ($attributes['charges'] ?? [] as $charge) {
            $this->chargeModel->create($charge + ['goods_receive_note_id' => $grn->id]);
        }

        return $grn;
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
    }

    private function formatHeader(array $attributes): array
    {
        return [
            'grn_no' => $attributes['grn_no'],
            'purchase_order_id' => $attributes['purchase_order_id'],
            'supplier_id' => $attributes['supplier_id'],
            'branch_id' => $attributes['branch_id'],
            'inventory_id' => $attributes['inventory_id'],
            'currency_id' => $attributes['currency_id'],
            'grn_date' => $attributes['grn_date'],
            'fee_allocation_method' => $attributes['fee_allocation_method'] ?? 'by_line_value',
            'tax_allocation_method' => $attributes['tax_allocation_method'] ?? 'by_weight',
            'remarks' => $attributes['remarks'] ?? null,
            'subtotal_amount' => $attributes['subtotal_amount'],
            'discount_amount' => $attributes['discount_amount'] ?? 0,
            'cargo_tax_amount' => $attributes['cargo_tax_amount'] ?? 0,
            'charge_total_amount' => $attributes['charge_total_amount'] ?? 0,
            'total_amount' => $attributes['total_amount'],
            'status' => $attributes['status'] ?? 'pending',
            'created_by' => $attributes['created_by'] ?? null,
            'approved_by' => $attributes['approved_by'] ?? null,
            'approved_at' => $attributes['approved_at'] ?? null,
        ];
    }
}
