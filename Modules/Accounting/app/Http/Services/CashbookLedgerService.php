<?php

namespace Modules\Accounting\app\Http\Services;

use Exception;
use Modules\Accounting\app\Http\Repositories\CashbookLedgerRepository;

class CashbookLedgerService
{
    protected $cashbook_ledger_repository;

    public function __construct(CashbookLedgerRepository $cashbook_ledger_repository)
    {
        $this->cashbook_ledger_repository = $cashbook_ledger_repository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        string $orderBy = 'created_at',
        array $searches = null,
        array $conditions = [],
        array $orConditions = [],
        array $with = [],
        ?array $whereHas = null,
        ?string $status = null
    ) {
        try {
            return $this->cashbook_ledger_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch cashbook ledger data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getDailyStatement(array $filters): array
    {
        try {
            return $this->cashbook_ledger_repository->getDailyStatement($filters);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch cashbook ledger daily statement: ' . $e->getMessage());
            throw $e;
        }
    }
}
