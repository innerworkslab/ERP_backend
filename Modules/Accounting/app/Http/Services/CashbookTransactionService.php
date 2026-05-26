<?php

namespace Modules\Accounting\app\Http\Services;

use DB;
use Exception;
use Modules\Accounting\app\Http\Repositories\CashbookTransactionRepository;

class CashbookTransactionService
{
    protected $cashbook_transaction_repository;

    public function __construct(CashbookTransactionRepository $cashbook_transaction_repository)
    {
        $this->cashbook_transaction_repository = $cashbook_transaction_repository;
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
            $result = $this->cashbook_transaction_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch cashbook transaction data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->cashbook_transaction_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch cashbook transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        DB::beginTransaction();
        try {
            $attributes['created_by'] = auth()->user()->id;
            $result = $this->cashbook_transaction_repository->create($attributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to create cashbook transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        DB::beginTransaction();
        try {
            $attributes['updated_by'] = auth()->user()->id;
            $result = $this->cashbook_transaction_repository->update($id, $attributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to update cashbook transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->cashbook_transaction_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete cashbook transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    public function confirm($id)
    {
        $this->cashbook_transaction_repository->confirm($id);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->cashbook_transaction_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find cashbook transaction with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
