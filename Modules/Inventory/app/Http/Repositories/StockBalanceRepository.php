<?php

namespace Modules\Inventory\app\Http\Repositories;

use Illuminate\Support\Facades\DB;

class StockBalanceRepository
{
    public function getDataWithPagination(
        int $perPage = 20,
        int $page = 1,
        array $filters = [],
        int $nearExpiryDays = 30
    ): array {
        $nearExpiryDays = max(1, $nearExpiryDays);

        $lotBalanceSub = DB::table('stock_movements as sm')
            ->selectRaw("sm.product_id, sm.inventory_id, COALESCE(sm.lot_no, '') as lot_no, SUM(sm.quantity) as on_hand_qty, SUM(sm.total_cost) as total_stock_value, MAX(sm.transaction_date) as last_movement_date")
            ->groupBy('sm.product_id', 'sm.inventory_id', DB::raw("COALESCE(sm.lot_no, '')"));

        $inventoryBranchSub = DB::table('branch_inventory as bi')
            ->join('branches as br', 'br.id', '=', 'bi.branch_id')
            ->where('bi.status', 'active')
            ->selectRaw("bi.inventory_id, GROUP_CONCAT(DISTINCT br.name ORDER BY br.name SEPARATOR ', ') as branch_names")
            ->groupBy('bi.inventory_id');

        $collectionSub = DB::table('collection_items as ci')
            ->join('collections as c', 'c.id', '=', 'ci.collection_id')
            ->selectRaw("ci.product_id, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as collection_names")
            ->groupBy('ci.product_id');

        $query = DB::query()
            ->fromSub($lotBalanceSub, 'lb')
            ->join('products as p', 'p.id', '=', 'lb.product_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'p.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('inventories as i', 'i.id', '=', 'lb.inventory_id')
            ->leftJoinSub($inventoryBranchSub, 'ib', function ($join) {
                $join->on('ib.inventory_id', '=', 'lb.inventory_id');
            })
            ->leftJoin('unit_of_measurements as uom', 'uom.id', '=', 'p.stock_uom_id')
            ->leftJoin('currencies as cur', 'cur.id', '=', 'p.purchase_currency_id')
            ->leftJoin('product_lots as pl', function ($join) {
                $join->on('pl.product_id', '=', 'lb.product_id')
                    ->on(DB::raw("COALESCE(pl.lot_no, '')"), '=', 'lb.lot_no');
            })
            ->leftJoinSub($collectionSub, 'pc', function ($join) {
                $join->on('pc.product_id', '=', 'p.id');
            })
            ->selectRaw("p.image_url as product_image,
                p.name as product_name,
                p.sku,
                NULL as barcode,
                cat.name as category_name,
                b.name as brand_name,
                ib.branch_names,
                i.name as inventory_name,
                uom.name as stock_uom,
                lb.on_hand_qty as on_hand_quantity,
                0 as reserved_quantity,
                lb.on_hand_qty as available_quantity,
                p.alert_quantity as reorder_level,
                CASE WHEN lb.on_hand_qty = 0 THEN 0 ELSE ROUND(lb.total_stock_value / lb.on_hand_qty, 4) END as unit_cost,
                lb.total_stock_value,
                ROUND(lb.total_stock_value * COALESCE(cur.exchange_rate, 1), 4) as base_currency_value,
                lb.lot_no,
                pl.expired_date,
                pl.serial_no,
                lb.last_movement_date,
                p.status,
                CASE
                    WHEN pl.expired_date IS NULL THEN NULL
                    WHEN pl.expired_date < CURDATE() THEN 'Expired'
                    WHEN pl.expired_date <= DATE_ADD(CURDATE(), INTERVAL " . $nearExpiryDays . " DAY) THEN 'Nearly Expired'
                    ELSE NULL
                END as expiry_remark,
                pc.collection_names")
            ->where('lb.on_hand_qty', '<>', 0);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('p.sku', 'LIKE', '%' . $search . '%')
                    ->orWhere('lb.lot_no', 'LIKE', '%' . $search . '%');
            });
        }

        if (!empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
            $query->whereExists(function ($exists) use ($branchId) {
                $exists->selectRaw('1')
                    ->from('branch_inventory as bi2')
                    ->whereColumn('bi2.inventory_id', 'lb.inventory_id')
                    ->where('bi2.branch_id', $branchId)
                    ->where('bi2.status', 'active');
            });
        }

        if (!empty($filters['inventory_id'])) {
            $query->where('lb.inventory_id', (int) $filters['inventory_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('lb.product_id', (int) $filters['product_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('p.category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('p.brand_id', (int) $filters['brand_id']);
        }

        if (!empty($filters['collection_id'])) {
            $collectionId = (int) $filters['collection_id'];
            $query->whereExists(function ($exists) use ($collectionId) {
                $exists->selectRaw('1')
                    ->from('collection_items as ci2')
                    ->whereColumn('ci2.product_id', 'p.id')
                    ->where('ci2.collection_id', $collectionId);
            });
        }

        if (!empty($filters['lot_no'])) {
            $query->where('lb.lot_no', $filters['lot_no']);
        }

        if (!empty($filters['expired_date_from'])) {
            $query->whereDate('pl.expired_date', '>=', $filters['expired_date_from']);
        }

        if (!empty($filters['expired_date_to'])) {
            $query->whereDate('pl.expired_date', '<=', $filters['expired_date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('p.status', $filters['status']);
        }

        if (!empty($filters['expiry_status'])) {
            if ($filters['expiry_status'] === 'expired') {
                $query->whereNotNull('pl.expired_date')->whereDate('pl.expired_date', '<', now()->toDateString());
            }

            if ($filters['expiry_status'] === 'nearly_expired') {
                $query->whereNotNull('pl.expired_date')
                    ->whereDate('pl.expired_date', '>=', now()->toDateString())
                    ->whereDate('pl.expired_date', '<=', now()->addDays($nearExpiryDays)->toDateString());
            }

            if ($filters['expiry_status'] === 'fresh') {
                $query->where(function ($q) use ($nearExpiryDays) {
                    $q->whereNull('pl.expired_date')
                        ->orWhereDate('pl.expired_date', '>', now()->addDays($nearExpiryDays)->toDateString());
                });
            }
        }

        $totalCount = (clone $query)->count();

        $results = $query
            ->orderBy('p.name')
            ->orderBy('i.name')
            ->orderBy('lb.lot_no')
            ->forPage($page, $perPage)
            ->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => (int) $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($totalCount / $perPage),
            ],
        ];
    }

    public function getProductLotTotals(int $productId, ?int $inventoryId = null, int $nearExpiryDays = 30)
    {
        $nearExpiryDays = max(1, $nearExpiryDays);

        $query = DB::table('stock_movements as sm')
            ->leftJoin('product_lots as pl', function ($join) {
                $join->on('pl.product_id', '=', 'sm.product_id')
                    ->on(DB::raw("COALESCE(pl.lot_no, '')"), '=', DB::raw("COALESCE(sm.lot_no, '')"));
            })
            ->selectRaw("sm.product_id,
                sm.inventory_id,
                COALESCE(sm.lot_no, '') as lot_no,
                pl.expired_date,
                pl.serial_no,
                SUM(sm.quantity) as total_qty,
                SUM(CASE WHEN pl.expired_date < CURDATE() THEN sm.quantity ELSE 0 END) as expired_qty,
                SUM(CASE WHEN pl.expired_date >= CURDATE() AND pl.expired_date <= DATE_ADD(CURDATE(), INTERVAL " . $nearExpiryDays . " DAY) THEN sm.quantity ELSE 0 END) as nearly_expired_qty")
            ->where('sm.product_id', $productId)
            ->groupBy('sm.product_id', 'sm.inventory_id', DB::raw("COALESCE(sm.lot_no, '')"), 'pl.expired_date', 'pl.serial_no')
            ->havingRaw('SUM(sm.quantity) <> 0')
            ->orderBy('lot_no');

        if ($inventoryId) {
            $query->where('sm.inventory_id', $inventoryId);
        }

        return $query->get();
    }
}
