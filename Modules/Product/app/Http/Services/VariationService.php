<?php

namespace Modules\Product\app\Http\Services;

use Exception;
use Modules\Product\app\Http\Repositories\VariationRepository;

class VariationService
{
    protected $variation_repository;

    public function __construct(VariationRepository $variation_repository)
    {
        $this->variation_repository = $variation_repository;
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
            $result = $this->variation_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch variation data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->variation_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch variation: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['created_by'] = auth()->user()->id;
            $result = $this->variation_repository->create($attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create variation: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->user()->id;
            $result = $this->variation_repository->update($id, $attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to update variation: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->variation_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete variation: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleVariationStatus($data)
    {
        $this->variation_repository->toggleActive($data);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->variation_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find variation with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
