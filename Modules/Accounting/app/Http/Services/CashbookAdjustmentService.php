<?php

namespace Modules\Accounting\app\Http\Services;

use DB;
use Exception;
use Modules\Accounting\app\Http\Repositories\CashbookAdjustmentRepository;

class CashbookAdjustmentService
{
    protected CashbookAdjustmentRepository $cashbookAdjustmentRepository;

    public function __construct(CashbookAdjustmentRepository $cashbookAdjustmentRepository)
    {
        $this->cashbookAdjustmentRepository = $cashbookAdjustmentRepository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        string $orderBy = 'created_at',
        ?array $searches = null,
        array $conditions = [],
        array $orConditions = [],
        array $with = [],
        ?array $whereHas = null,
        ?string $status = null
    ) {
        try {
            return $this->cashbookAdjustmentRepository->getDataWithPagination(
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
            logger()->error('Error : Failed to fetch cashbook adjustment list: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->cashbookAdjustmentRepository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch cashbook adjustment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        DB::beginTransaction();
        try {
            $result = $this->cashbookAdjustmentRepository->create($attributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to create cashbook adjustment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        DB::beginTransaction();
        try {
            $result = $this->cashbookAdjustmentRepository->update($id, $attributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to update cashbook adjustment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function approve(int $id)
    {
        DB::beginTransaction();
        try {
            $result = $this->cashbookAdjustmentRepository->approve($id);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to approve cashbook adjustment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function reject(int $id)
    {
        DB::beginTransaction();
        try {
            $result = $this->cashbookAdjustmentRepository->reject($id);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to reject cashbook adjustment: ' . $e->getMessage());
            throw $e;
        }
    }
}

