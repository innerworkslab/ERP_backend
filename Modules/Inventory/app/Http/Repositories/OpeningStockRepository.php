<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Models\OpeningStock;
use Modules\Inventory\app\Models\OpeningStockLine;
use Modules\Inventory\app\Models\ProductLots;
use Modules\Product\app\Models\Product;

class OpeningStockRepository extends BaseRepo
{
    protected $opening_stock_line;
    protected $product_lots;

    public function __construct(OpeningStock $model, OpeningStockLine $opening_stock_line, ProductLots $product_lots)
    {
        parent::__construct($model);
        $this->opening_stock_line = $opening_stock_line;
        $this->product_lots = $product_lots;
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
            $line['lot_no'] = $this->resolveLotNo($line);

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

            $this->syncProductLot($line);
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
            $line['lot_no'] = $this->resolveLotNo($line);

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

            $this->syncProductLot($line);
        }

        return $openingStock;
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

    private function syncProductLot(array $line): void
    {
        if (empty($line['lot_no']) || empty($line['product_id'])) {
            return;
        }

        $lot = $this->product_lots->newQuery()->firstOrCreate(
            [
                'product_id' => $line['product_id'],
                'lot_no' => $line['lot_no'],
            ],
            [
                'expired_date' => $line['expired_date'] ?? null,
                'serial_no' => $line['serial_no'] ?? null,
            ]
        );

        $lot->update([
            'expired_date' => $line['expired_date'] ?? $lot->expired_date,
            'serial_no' => $line['serial_no'] ?? $lot->serial_no,
        ]);
    }
    //format LOT-{SKU}_XX where XX is a sequential number for each product's lot
    private function resolveLotNo(array $line): ?string
    {
        $providedLotNo = trim((string) ($line['lot_no'] ?? ''));
        if ($providedLotNo !== '') {
            return $providedLotNo;
        }

        $productId = (int) ($line['product_id'] ?? 0);
        if ($productId <= 0) {
            return null;
        }

        $product = Product::query()->find($productId, ['id', 'sku']);
        $sku = trim((string) ($product->sku ?? ''));
        if ($sku === '') {
            return null;
        }

        $prefix = 'LOT-' . $sku . '_';
        $lotNos = $this->product_lots->newQuery()
            ->where('product_id', $productId)
            ->where('lot_no', 'LIKE', $prefix . '%')
            ->pluck('lot_no');

        $maxSequence = 0;
        foreach ($lotNos as $lotNo) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $lotNo, $matches)) {
                $sequence = (int) $matches[1];
                if ($sequence > $maxSequence) {
                    $maxSequence = $sequence;
                }
            }
        }

        return $prefix . str_pad((string) ($maxSequence + 1), 2, '0', STR_PAD_LEFT);
    }
}
