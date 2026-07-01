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

    public function getListWithFilters(
        int $perPage,
        int $page,
        ?string $search = null,
        ?string $returnNo = null,
        array $conditions = [],
        ?string $returnDateFrom = null,
        ?string $returnDateTo = null
    ): array {
        $query = $this->buildListQuery($search, $returnNo, $conditions, $returnDateFrom, $returnDateTo);
        $total = (clone $query)->count();
        $results = $query
            ->orderByDesc('created_at')
            ->offset(max($page - 1, 0) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($total / max($perPage, 1)),
            ],
        ];
    }

    private function buildListQuery(
        ?string $search,
        ?string $returnNo,
        array $conditions,
        ?string $returnDateFrom,
        ?string $returnDateTo
    ) {
        $query = $this->model->newQuery()->with([
            'goodsReceiveNote',
            'purchaseOrder',
            'supplier',
            'branch',
            'inventory',
            'currency',
        ]);

        $this->applySearchFilter($query, $search);
        $this->applyReturnNoFilter($query, $returnNo);
        $this->applyExactFilters($query, $conditions);
        $this->applyDateFilters($query, $returnDateFrom, $returnDateTo);

        return $query;
    }

    private function applySearchFilter($query, ?string $search): void
    {
        if (empty($search)) {
            return;
        }

        $query->where(function ($searchQuery) use ($search) {
            $searchQuery->where('return_no', 'like', '%' . $search . '%')
                ->orWhere('remarks', 'like', '%' . $search . '%')
                ->orWhereHas('goodsReceiveNote', function ($q) use ($search) {
                    $q->where('grn_no', 'like', '%' . $search . '%');
                })
                ->orWhereHas('supplier', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('branch', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('inventory', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    private function applyReturnNoFilter($query, ?string $returnNo): void
    {
        if (empty($returnNo)) {
            return;
        }

        $query->where('return_no', 'like', '%' . $returnNo . '%');
    }

    private function applyExactFilters($query, array $conditions): void
    {
        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }
    }

    private function applyDateFilters($query, ?string $returnDateFrom, ?string $returnDateTo): void
    {
        if (!empty($returnDateFrom)) {
            $query->whereDate('return_date', '>=', $returnDateFrom);
        }

        if (!empty($returnDateTo)) {
            $query->whereDate('return_date', '<=', $returnDateTo);
        }
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
            'return_date' => $attributes['return_date'],
            'return_type' => $attributes['return_type'],
            'exchange_type' => $attributes['exchange_type'] ?? null,
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
