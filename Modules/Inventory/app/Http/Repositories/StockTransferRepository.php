<?php

namespace Modules\Inventory\app\Http\Repositories;

use RuntimeException;
use Modules\Inventory\app\Models\ProductLots;
use Modules\Inventory\app\Models\StockTransfer;
use Modules\Inventory\app\Models\StockTransferLine;

class StockTransferRepository extends BaseRepo
{
    protected $stock_transfer_line;
    protected $product_lots;

    public function __construct(StockTransfer $model, StockTransferLine $stock_transfer_line, ProductLots $product_lots)
    {
        parent::__construct($model);
        $this->stock_transfer_line = $stock_transfer_line;
        $this->product_lots = $product_lots;
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'source_inventory',
                'target_inventory',
                'lines.product',
                'lines.uom',
                'created_by',
                'updated_by',
                'confirmed_by',
                'rejected_by',
            ]);
        }

        return $data;
    }

    public function create(array $attributes)
    {
        $stockTransfer = $this->model->create([
            'reference_id' => $attributes['reference_id'],
            'transfer_date' => $attributes['transfer_date'],
            'source_inventory_id' => $attributes['source_inventory_id'],
            'target_inventory_id' => $attributes['target_inventory_id'],
            'remarks' => $attributes['remarks'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'created_by' => $attributes['created_by'],
        ]);

        $lines = $attributes['lines'] ?? [];

        foreach ($lines as $line) {
            $this->assertLotExistsForProduct($line);

            $this->stock_transfer_line->create([
                'stock_transfer_id' => $stockTransfer->id,
                'product_id' => $line['product_id'],
                'lot_no' => $line['lot_no'],
                'quantity' => $line['quantity'],
                'uom_id' => $line['uom_id'],
                'remarks' => $line['remarks'] ?? null,
            ]);
        }

        return $stockTransfer;
    }

    public function updateWithLines(int $id, array $attributes)
    {
        $stockTransfer = $this->find($id);

        if (!$stockTransfer) {
            return null;
        }

        $stockTransfer->update([
            'transfer_date' => $attributes['transfer_date'],
            'source_inventory_id' => $attributes['source_inventory_id'],
            'target_inventory_id' => $attributes['target_inventory_id'],
            'remarks' => $attributes['remarks'] ?? null,
            'status' => $attributes['status'] ?? 'pending',
            'updated_by' => $attributes['updated_by'],
        ]);

        $this->stock_transfer_line->where('stock_transfer_id', $stockTransfer->id)->delete();

        foreach ($attributes['lines'] as $line) {
            $this->assertLotExistsForProduct($line);

            $this->stock_transfer_line->create([
                'stock_transfer_id' => $stockTransfer->id,
                'product_id' => $line['product_id'],
                'lot_no' => $line['lot_no'],
                'quantity' => $line['quantity'],
                'uom_id' => $line['uom_id'],
                'remarks' => $line['remarks'] ?? null,
            ]);
        }

        return $stockTransfer;
    }

    public function delete($id)
    {
        $model = $this->find($id);

        if ($model) {
            $this->stock_transfer_line->where('stock_transfer_id', $model->id)->delete();
            $model->delete();

            return true;
        }

        return false;
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
    }

    public function getDataWithPagination($perPage = 10, $page = 1, $orderBy = 'created_at', $searches = null, $conditions = [], $orConditions = [], $with = [], $whereHas = null, $status = null)
    {
        $query = $this->model->query()->withCount(['lines as product_count']);

        if (count($with) > 0) {
            $query->with($with);
        }

        if ($whereHas && is_array($whereHas)) {
            foreach ($whereHas as $relation => $constraint) {
                $query->whereHas($relation, $constraint);
            }
        }

        $offset = $perPage * ($page - 1);

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        $query->limit($perPage);

        if (!empty($status)) {
            if ($status === 'inactive') {
                $query->where('status', 'inactive');
            } elseif ($status === 'active') {
                $query->where('status', 'active');
            } elseif ($status === 'closed') {
                $query->where('status', 'closed');
            } elseif ($status === 'open') {
                $query->where('status', 'open');
            }
        }

        if ($searches) {
            $query->where(function ($q) use ($searches) {
                foreach ($searches as $key => $value) {
                    $q->orWhere($key, 'LIKE', "%$value%");
                }
            });
        }

        foreach ($conditions as $key => $condition) {
            $query->where($key, $condition);
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }

        $totalCount = $query->count();

        if ($offset > 0) {
            $query->offset($offset);
        }

        $totalPages = ceil($totalCount / $perPage);
        $results = $query->latest()->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ];
    }

    private function assertLotExistsForProduct(array $line): void
    {
        $productId = (int) ($line['product_id'] ?? 0);
        $lotNo = trim((string) ($line['lot_no'] ?? ''));

        if ($productId <= 0 || $lotNo === '') {
            throw new RuntimeException('Each line requires valid product_id and lot_no.');
        }

        $exists = $this->product_lots->newQuery()
            ->where('product_id', $productId)
            ->where('lot_no', $lotNo)
            ->exists();

        if (!$exists) {
            throw new RuntimeException('Lot "' . $lotNo . '" is not registered for the selected product.');
        }
    }

}
