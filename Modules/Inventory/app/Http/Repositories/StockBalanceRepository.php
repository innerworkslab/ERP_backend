<?php

namespace Modules\Inventory\app\Http\Repositories;

use Carbon\Carbon;
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

        $dateFrom = Carbon::parse($filters['date_from'] ?? now()->toDateString())->toDateString();
        $dateTo = Carbon::parse($filters['date_to'] ?? $dateFrom)->toDateString();
        $expiryLimitDate = Carbon::parse($dateTo)->addDays($nearExpiryDays)->toDateString();

        $lotBalanceSub = DB::table('stock_movements as sm')
            ->selectRaw(
                "sm.product_id,
                sm.inventory_id,
                COALESCE(sm.lot_no, '') as lot_no,
                SUM(CASE WHEN DATE(sm.transaction_date) < '{$dateFrom}' THEN sm.quantity ELSE 0 END) as opening_qty,
                SUM(CASE WHEN DATE(sm.transaction_date) BETWEEN '{$dateFrom}' AND '{$dateTo}' THEN sm.quantity ELSE 0 END) as period_qty,
                SUM(CASE WHEN DATE(sm.transaction_date) <= '{$dateTo}' THEN sm.quantity ELSE 0 END) as closing_qty,
                SUM(CASE WHEN DATE(sm.transaction_date) < '{$dateFrom}' THEN sm.total_cost ELSE 0 END) as opening_cost,
                SUM(CASE WHEN DATE(sm.transaction_date) BETWEEN '{$dateFrom}' AND '{$dateTo}' THEN sm.total_cost ELSE 0 END) as period_cost,
                SUM(CASE WHEN DATE(sm.transaction_date) <= '{$dateTo}' THEN sm.total_cost ELSE 0 END) as closing_cost,
                MAX(sm.transaction_date) as last_movement_date"
            )
            ->whereDate('sm.transaction_date', '<=', $dateTo)
            ->groupBy('sm.product_id', 'sm.inventory_id', DB::raw("COALESCE(sm.lot_no, '')"));

        $productOnHandSub = DB::query()
            ->fromSub($lotBalanceSub, 'lb2')
            ->selectRaw('lb2.product_id, lb2.inventory_id, SUM(lb2.closing_qty) as product_on_hand_quantity')
            ->groupBy('lb2.product_id', 'lb2.inventory_id');

        $reservedQuantitySub = DB::table('sale_invoice_items as sii')
            ->join('sale_invoices as si', 'si.id', '=', 'sii.sale_invoice_id')
            ->join('products as p2', 'p2.id', '=', 'sii.product_id')
            ->leftJoin('unit_of_measurement_conversions as uomc', function ($join) {
                $join->on('uomc.base_unit_id', '=', 'p2.stock_uom_id')
                    ->on('uomc.conversion_unit_id', '=', 'sii.uom_id')
                    ->where('uomc.status', '=', 'active');
            })
            ->where('si.status', 'reserved')
            ->selectRaw('sii.product_id, si.inventory_id, SUM(CASE
                WHEN sii.uom_id = p2.stock_uom_id THEN COALESCE(sii.reserved_qty, 0)
                WHEN uomc.conversion_rate IS NOT NULL THEN COALESCE(sii.reserved_qty, 0) / uomc.conversion_rate
                ELSE COALESCE(sii.reserved_qty, 0)
            END) as reserved_quantity')
            ->groupBy('sii.product_id', 'si.inventory_id');

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
            ->leftJoinSub($productOnHandSub, 'ph', function ($join) {
                $join->on('ph.product_id', '=', 'lb.product_id')
                    ->on('ph.inventory_id', '=', 'lb.inventory_id');
            })
            ->leftJoinSub($reservedQuantitySub, 'rs', function ($join) {
                $join->on('rs.product_id', '=', 'lb.product_id')
                    ->on('rs.inventory_id', '=', 'lb.inventory_id');
            })
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
            ->selectRaw(
                "p.image_url as product_image,
                p.name as product_name,
                p.sku,
                NULL as barcode,
                cat.name as category_name,
                b.name as brand_name,
                ib.branch_names,
                i.name as inventory_name,
                uom.name as stock_uom,
                lb.opening_qty as opening_quantity,
                lb.period_qty as movement_quantity,
                lb.closing_qty as closing_quantity,
                lb.closing_qty as running_quantity,
                lb.opening_cost as opening_stock_value,
                lb.period_cost as movement_stock_value,
                lb.closing_cost as closing_stock_value,
                lb.closing_qty as on_hand_quantity,
                CASE
                    WHEN COALESCE(ph.product_on_hand_quantity, 0) > 0
                    THEN ROUND(COALESCE(rs.reserved_quantity, 0) * (lb.closing_qty / ph.product_on_hand_quantity), 2)
                    ELSE 0
                END as reserved_quantity,
                CASE
                    WHEN COALESCE(ph.product_on_hand_quantity, 0) > 0
                    THEN ROUND(lb.closing_qty - (COALESCE(rs.reserved_quantity, 0) * (lb.closing_qty / ph.product_on_hand_quantity)), 2)
                    ELSE lb.closing_qty
                END as available_quantity,
                p.alert_quantity as reorder_level,
                CASE WHEN lb.closing_qty = 0 THEN 0 ELSE ROUND(lb.closing_cost / lb.closing_qty, 4) END as unit_cost,
                lb.closing_cost as total_stock_value,
                ROUND(lb.closing_cost * COALESCE(cur.exchange_rate, 1), 4) as base_currency_value,
                lb.lot_no,
                pl.expired_date,
                pl.serial_no,
                lb.last_movement_date,
                p.status,
                CASE
                    WHEN pl.expired_date IS NULL THEN NULL
                    WHEN pl.expired_date < '{$dateTo}' THEN 'Expired'
                    WHEN pl.expired_date <= DATE_ADD('{$dateTo}', INTERVAL {$nearExpiryDays} DAY) THEN 'Nearly Expired'
                    ELSE NULL
                END as expiry_remark,
                pc.collection_names"
            )
            ->where('lb.closing_qty', '<>', 0);

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
                $query->whereNotNull('pl.expired_date')->whereDate('pl.expired_date', '<', $dateTo);
            }

            if ($filters['expiry_status'] === 'nearly_expired') {
                $query->whereNotNull('pl.expired_date')
                    ->whereDate('pl.expired_date', '>=', $dateTo)
                    ->whereDate('pl.expired_date', '<=', $expiryLimitDate);
            }

            if ($filters['expiry_status'] === 'fresh') {
                $query->where(function ($q) use ($expiryLimitDate) {
                    $q->whereNull('pl.expired_date')
                        ->orWhereDate('pl.expired_date', '>', $expiryLimitDate);
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
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ];
    }

    public function getProductSummary(int $inventoryId, int $productId): array
    {
        $onHandQuantity = (float) DB::table('stock_movements as sm')
            ->where('sm.inventory_id', $inventoryId)
            ->where('sm.product_id', $productId)
            ->sum('sm.quantity');

        $reservedQuantity = (float) DB::table('sale_invoice_items as sii')
            ->join('sale_invoices as si', 'si.id', '=', 'sii.sale_invoice_id')
            ->where('si.inventory_id', $inventoryId)
            ->where('si.status', 'reserved')
            ->where('sii.product_id', $productId)
            ->sum(DB::raw('COALESCE(sii.reserved_qty, 0)'));

        return [
            'on_hand_quantity' => round($onHandQuantity, 2),
            'reserved_quantity' => round($reservedQuantity, 2),
            'available_quantity' => round($onHandQuantity - $reservedQuantity, 2),
        ];
    }

    public function getLotBalancesForProduct(int $inventoryId, int $productId): array
    {
        $lotBalanceSub = DB::table('stock_movements as sm')
            ->selectRaw(
                "sm.product_id,
                sm.inventory_id,
                COALESCE(sm.lot_no, '') as lot_no,
                SUM(sm.quantity) as closing_qty,
                MAX(sm.transaction_date) as last_movement_date"
            )
            ->where('sm.inventory_id', $inventoryId)
            ->where('sm.product_id', $productId)
            ->groupBy('sm.product_id', 'sm.inventory_id', DB::raw("COALESCE(sm.lot_no, '')"));

        $productOnHandSub = DB::query()
            ->fromSub($lotBalanceSub, 'lb2')
            ->selectRaw('lb2.product_id, lb2.inventory_id, SUM(lb2.closing_qty) as product_on_hand_quantity')
            ->groupBy('lb2.product_id', 'lb2.inventory_id');

        $reservedQuantitySub = DB::table('sale_invoice_items as sii')
            ->join('sale_invoices as si', 'si.id', '=', 'sii.sale_invoice_id')
            ->join('products as p2', 'p2.id', '=', 'sii.product_id')
            ->leftJoin('unit_of_measurement_conversions as uomc', function ($join) {
                $join->on('uomc.base_unit_id', '=', 'p2.stock_uom_id')
                    ->on('uomc.conversion_unit_id', '=', 'sii.uom_id')
                    ->where('uomc.status', '=', 'active');
            })
            ->where('si.inventory_id', $inventoryId)
            ->where('si.status', 'reserved')
            ->where('sii.product_id', $productId)
            ->selectRaw('sii.product_id, si.inventory_id, SUM(CASE
                WHEN sii.uom_id = p2.stock_uom_id THEN COALESCE(sii.reserved_qty, 0)
                WHEN uomc.conversion_rate IS NOT NULL THEN COALESCE(sii.reserved_qty, 0) / uomc.conversion_rate
                ELSE COALESCE(sii.reserved_qty, 0)
            END) as reserved_quantity')
            ->groupBy('sii.product_id', 'si.inventory_id');

        return DB::query()
            ->fromSub($lotBalanceSub, 'lb')
            ->leftJoin('product_lots as pl', function ($join) {
                $join->on('pl.product_id', '=', 'lb.product_id')
                    ->on(DB::raw("COALESCE(pl.lot_no, '')"), '=', 'lb.lot_no');
            })
            ->leftJoinSub($productOnHandSub, 'ph', function ($join) {
                $join->on('ph.product_id', '=', 'lb.product_id')
                    ->on('ph.inventory_id', '=', 'lb.inventory_id');
            })
            ->leftJoinSub($reservedQuantitySub, 'rs', function ($join) {
                $join->on('rs.product_id', '=', 'lb.product_id')
                    ->on('rs.inventory_id', '=', 'lb.inventory_id');
            })
            ->selectRaw(
                "pl.id as product_lot_id,
                lb.product_id,
                lb.inventory_id,
                lb.lot_no,
                pl.expired_date,
                pl.serial_no,
                lb.closing_qty as on_hand_quantity,
                CASE
                    WHEN COALESCE(ph.product_on_hand_quantity, 0) > 0
                    THEN ROUND(COALESCE(rs.reserved_quantity, 0) * (lb.closing_qty / ph.product_on_hand_quantity), 2)
                    ELSE 0
                END as reserved_quantity,
                CASE
                    WHEN COALESCE(ph.product_on_hand_quantity, 0) > 0
                    THEN ROUND(lb.closing_qty - (COALESCE(rs.reserved_quantity, 0) * (lb.closing_qty / ph.product_on_hand_quantity)), 2)
                    ELSE lb.closing_qty
                END as available_quantity,
                lb.last_movement_date,
                pl.created_at"
            )
            ->where('lb.closing_qty', '<>', 0)
            ->orderBy('pl.created_at')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
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
