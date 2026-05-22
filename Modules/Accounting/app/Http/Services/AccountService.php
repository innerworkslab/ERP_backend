<?php

namespace Modules\Accounting\app\Http\Services;

use Exception;
use Modules\Accounting\app\Http\Repositories\AccountRepository;

class AccountService
{
    protected $account_repository;

    public function __construct(AccountRepository $account_repository)
    {
        $this->account_repository = $account_repository;
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
            $result = $this->account_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch account data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }
}
