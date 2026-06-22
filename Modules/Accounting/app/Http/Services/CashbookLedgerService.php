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
