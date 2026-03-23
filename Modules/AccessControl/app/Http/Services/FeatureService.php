<?php

namespace Modules\AccessControl\app\Http\Services;

use Exception;
use Modules\AccessControl\app\Http\Repositories\FeatureRepository;

class FeatureService
{
    protected $feature_repository;

    public function __construct(FeatureRepository $feature_repository)
    {
        $this->feature_repository = $feature_repository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        string $orderBy = 'created_at',
        array $searches = null,
        array $conditions = [],
        array $with = [],
        ?array $filters = null,
        ?string $status = null
    ) {
        try {
            $result = $this->feature_repository->getDataWithPaginationAndFilters(
                $perPage,
                $page,
                $orderBy,
                $searches,
                $conditions,
                $with,
                $filters,
                $status
            );
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch feature data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->feature_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch feature: ' . $e->getMessage());
            throw $e;
        }
    }

    public function assignRoles(array $attributes)
    {
        try {
            $this->feature_repository->assignRoles($attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to assign roles to feature: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleFeatureStatus($data)
    {
        $this->feature_repository->toggleActive($data);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->feature_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find role with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    public function recommendedFeatures($roleId)
    {
        try {
            $result = $this->feature_repository->recommendedFeatures($roleId);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch recommended features: ' . $e->getMessage());
            throw $e;
        }
    }

    public function otherFeatures($roleId)
    {
        try {
            $result = $this->feature_repository->otherFeatures($roleId);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch another features: ' . $e->getMessage());
            throw $e;
        }
    }
}
