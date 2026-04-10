<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Modules\Inventory\app\Http\Repositories\StockBalanceRepository;

class StockBalanceService
{
    protected $stock_balance_repository;

    public function __construct(StockBalanceRepository $stock_balance_repository)
    {
        $this->stock_balance_repository = $stock_balance_repository;
    }

    public function getDataWithPagination(
        int $perPage = 20,
        int $page = 1,
        array $filters = [],
        int $nearExpiryDays = 30
    ) {
        try {
            return $this->stock_balance_repository->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                filters: $filters,
                nearExpiryDays: $nearExpiryDays
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch stock balance data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getProductLotTotals(int $productId, ?int $inventoryId = null, int $nearExpiryDays = 30)
    {
        try {
            return $this->stock_balance_repository->getProductLotTotals(
                productId: $productId,
                inventoryId: $inventoryId,
                nearExpiryDays: $nearExpiryDays
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch product lot totals: ' . $e->getMessage());
            throw $e;
        }
    }
}
