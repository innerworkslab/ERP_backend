<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Models\PurchaseOrder;
use Modules\Inventory\app\Models\PurchaseOrderLine;

class PurchaseOrderRepository extends BaseRepo
{
    protected $purchase_order_line;

    public function __construct(PurchaseOrder $model, PurchaseOrderLine $purchase_order_line)
    {
        parent::__construct($model);
        $this->purchase_order_line = $purchase_order_line;
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'supplier',
                'branch',
                'inventory',
                'currency',
                'createdBy',
                'lines.product',
                'lines.uom',
                'lines.tax',
            ]);
        }

        return $data;
    }

    public function create(array $attributes)
    {
        $purchaseOrder = $this->model->create([
            'po_number' => $attributes['po_number'],
            'po_date' => $attributes['po_date'],
            'supplier_id' => $attributes['supplier_id'],
            'branch_id' => $attributes['branch_id'],
            'inventory_id' => $attributes['inventory_id'],
            'currency_id' => $attributes['currency_id'],
            'subtotal_amount' => $attributes['subtotal_amount'],
            'discount_amount' => $attributes['discount_amount'] ?? 0,
            'tax_amount' => $attributes['tax_amount'] ?? 0,
            'total_amount' => $attributes['total_amount'],
            'status' => $attributes['status'] ?? 'pending',
            'payment_status' => $attributes['payment_status'] ?? 'unpaid',
            'delivery_status' => $attributes['delivery_status'] ?? 'not_delivered',
            'remarks' => $attributes['remarks'] ?? null,
            'created_by' => $attributes['created_by'] ?? null,
        ]);

        foreach (($attributes['lines'] ?? []) as $line) {
            $this->purchase_order_line->create($this->formatLineAttributes($line, $purchaseOrder->id));
        }

        return $purchaseOrder;
    }

    public function updateWithLines(int $id, array $attributes)
    {
        $purchaseOrder = $this->find($id);

        if (!$purchaseOrder) {
            return null;
        }

        $purchaseOrder->update([
            'po_date' => $attributes['po_date'],
            'supplier_id' => $attributes['supplier_id'],
            'branch_id' => $attributes['branch_id'],
            'inventory_id' => $attributes['inventory_id'],
            'currency_id' => $attributes['currency_id'],
            'subtotal_amount' => $attributes['subtotal_amount'],
            'discount_amount' => $attributes['discount_amount'] ?? 0,
            'tax_amount' => $attributes['tax_amount'] ?? 0,
            'total_amount' => $attributes['total_amount'],
            'remarks' => $attributes['remarks'] ?? null,
        ]);

        $this->purchase_order_line->where('purchase_order_id', $purchaseOrder->id)->delete();

        foreach (($attributes['lines'] ?? []) as $line) {
            $this->purchase_order_line->create($this->formatLineAttributes($line, $purchaseOrder->id));
        }

        return $purchaseOrder;
    }

    public function delete($id)
    {
        $model = $this->find($id);

        if ($model) {
            $this->purchase_order_line->where('purchase_order_id', $model->id)->delete();
            $model->delete();

            return true;
        }

        return false;
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
    }

    private function formatLineAttributes(array $line, int $purchaseOrderId): array
    {
        $quantity = (float) ($line['quantity'] ?? 0);
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $grossAmount = (float) ($line['gross_amount'] ?? ($quantity * $unitPrice));

        return [
            'purchase_order_id' => $purchaseOrderId,
            'product_id' => $line['product_id'],
            'uom_id' => $line['uom_id'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'gross_amount' => $grossAmount,
            'discount_type' => $line['discount_type'] ?? null,
            'discount_value' => $line['discount_value'] ?? null,
            'discount_amount' => $line['discount_amount'] ?? 0,
            'tax_id' => $line['tax_id'] ?? null,
            'tax_amount' => $line['tax_amount'] ?? 0,
            'line_total' => $line['line_total'] ?? $line['total_amount'],
        ];
    }
}
