<?php

namespace Modules\Accounting\app\Http\Services;

use DB;
use Exception;
use Modules\Accounting\app\Http\Repositories\CashbookTransferRepository;

class CashbookTransferService
{
    protected CashbookTransferRepository $cashbook_transfer_repository;

    public function __construct(CashbookTransferRepository $cashbook_transfer_repository)
    {
        $this->cashbook_transfer_repository = $cashbook_transfer_repository;
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
            return $this->cashbook_transfer_repository->getDataWithPagination(
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
            logger()->error('Error : Failed to fetch cashbook transfer data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->cashbook_transfer_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch cashbook transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        DB::beginTransaction();

        try {
            $attributes['created_by'] = auth()->user()->id;
            $result = $this->cashbook_transfer_repository->create($attributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to create cashbook transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        DB::beginTransaction();

        try {
            $attributes['updated_by'] = auth()->user()->id;
            $result = $this->cashbook_transfer_repository->update($id, $attributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to update cashbook transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function confirmTransfer(int $id)
    {
        DB::beginTransaction();

        try {
            $result = $this->cashbook_transfer_repository->confirmTransfer($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to confirm cashbook transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rejectTransfer(int $id)
    {
        DB::beginTransaction();

        try {
            $result = $this->cashbook_transfer_repository->rejectTransfer($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to reject cashbook transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->cashbook_transfer_repository->whereFirst($column, $value);

            return $result ?: null;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find cashbook transfer with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
