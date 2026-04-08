<?php

namespace Modules\Inventory\app\Http\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\app\Models\StockMovement;

class StockMovementRepository extends BaseRepo
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    public function getDataWithPagination(
        $perPage = 10,
        $page = 1,
        $orderBy = 'transaction_date',
        $searches = null,
        $conditions = [],
        $orConditions = [],
        $with = [],
        $whereHas = null,
        $status = null
    ) {
        $query = DB::table('stock_movements as sm')
            ->selectRaw("sm.id,
                sm.transaction_date,
                sm.reference_type,
                sm.reference_id,
                sm.voucher_no,
                sm.product_name,
                sm.sku,
                sm.inventory_name,
                sm.branch_name,
                sm.movement_type,
                CASE
                    WHEN sm.quantity > 0 THEN CONCAT('+', CAST(sm.quantity AS CHAR))
                    ELSE CAST(sm.quantity AS CHAR)
                END AS quantity,
                sm.UOM,
                sm.unit_cost,
                sm.total_cost,
                sm.balance_quantity_before,
                sm.balance_quantity_after,
                sm.balance_cost_before,
                sm.balance_cost_after,
                sm.created_at");

        if (!empty($searches)) {
            $query->where(function ($q) use ($searches) {
                if (!empty($searches['product_search'])) {
                    $productSearch = $searches['product_search'];

                    $q->orWhere(function ($productQuery) use ($productSearch) {
                        $productQuery->where('sm.product_name', 'LIKE', '%' . $productSearch . '%')
                            ->orWhere('sm.sku', 'LIKE', '%' . $productSearch . '%');
                    });
                }

                foreach ($searches as $column => $value) {
                    if (!in_array($column, ['voucher_no', 'product_name', 'sku', 'inventory_name', 'branch_name'], true)) {
                        continue;
                    }

                    $q->orWhere('sm.' . $column, 'LIKE', '%' . $value . '%');
                }
            });
        }

        if (!empty($conditions['reference_type'])) {
            $query->where('sm.reference_type', $conditions['reference_type']);
        }

        if (!empty($conditions['reference_id'])) {
            $query->where('sm.reference_id', $conditions['reference_id']);
        }

        if (!empty($conditions['transaction_date_from'])) {
            $query->whereDate('sm.transaction_date', '>=', $conditions['transaction_date_from']);
        }

        if (!empty($conditions['transaction_date_to'])) {
            $query->whereDate('sm.transaction_date', '<=', $conditions['transaction_date_to']);
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }

        $totalCount = (clone $query)->count();

        $sortableColumns = [
            'transaction_date' => 'sm.transaction_date',
            'created_at' => 'sm.created_at',
            'id' => 'sm.id',
            'voucher_no' => 'sm.voucher_no',
            'product_name' => 'sm.product_name',
            'sku' => 'sm.sku',
        ];

        $orderColumn = $sortableColumns[$orderBy] ?? 'sm.transaction_date';

        $results = $query
            ->orderBy($orderColumn, 'desc')
            ->orderBy('sm.id', 'desc')
            ->forPage($page, $perPage)
            ->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => (int) $perPage,
                'current_page' => (int) $page,
                'total_pages' => (int) ceil($totalCount / $perPage),
            ],
        ];
    }
}
