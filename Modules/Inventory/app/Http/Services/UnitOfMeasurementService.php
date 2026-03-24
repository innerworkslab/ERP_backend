<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Modules\Inventory\app\Http\Repositories\UnitOfMeasurementRepository;

class UnitOfMeasurementService
{
    protected $uom_repository;

    public function __construct(UnitOfMeasurementRepository $uom_repository)
    {
        $this->uom_repository = $uom_repository;
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
            $result = $this->uom_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch uom data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->uom_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch uom: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['created_by'] = auth()->user()->id;
            $result = $this->uom_repository->create($attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create uom: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->user()->id;
            $result = $this->uom_repository->update($id, $attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to update uom: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->uom_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete uom: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleUOMStatus($data)
    {
        $this->uom_repository->toggleActive($data);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->uom_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find uom with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
