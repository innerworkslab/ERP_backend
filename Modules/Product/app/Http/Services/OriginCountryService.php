<?php

namespace Modules\Product\app\Http\Services;


use Exception;
use Modules\Product\app\Http\Repositories\OriginCountryRepository;

class OriginCountryService
{
    protected $origin_country_repository;

    public function __construct(OriginCountryRepository $origin_country_repository)
    {
        $this->origin_country_repository = $origin_country_repository;
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
            $perPage = max(1, $perPage);
            $page = max(1, $page);

            $result = $this->origin_country_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch origin country data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->origin_country_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch origin country: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $result = $this->origin_country_repository->create($attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create origin country: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $result = $this->origin_country_repository->update($id, $attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to update origin country: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->origin_country_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete origin country: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->origin_country_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find origin country with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
