<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Modules\Inventory\app\Http\Repositories\StockMovementRepository;

class StockMovementService
{
    protected $stock_movement_repository;

    public function __construct(StockMovementRepository $stock_movement_repository)
    {
        $this->stock_movement_repository = $stock_movement_repository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        string $orderBy = 'transaction_date',
        array $searches = null,
        array $conditions = [],
        array $orConditions = [],
        array $with = [],
        ?array $whereHas = null,
        ?string $status = null
    ) {
        try {
            return $this->stock_movement_repository->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                orderBy: $orderBy,
                searches: $searches,
                conditions: $conditions,
                orConditions: $orConditions,
                with: $with,
                whereHas: $whereHas,
                status: $status
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch stock movement data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }
}
