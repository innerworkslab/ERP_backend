<?php

namespace Modules\Staff\app\Http\Services;

use Exception;
use Modules\Staff\app\Http\Repositories\FeatureRecommendationRuleRepository;

class FeatureRecommendationRuleService
{
    protected $feature_recommendation_rule_repository;

    public function __construct(FeatureRecommendationRuleRepository $feature_recommendation_rule_repository)
    {
        $this->feature_recommendation_rule_repository = $feature_recommendation_rule_repository;
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
            return $this->feature_recommendation_rule_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                orderBy: $orderBy,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions,
                orConditions: $orConditions,
                whereHas: $whereHas
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch feature recommendation rule list: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->feature_recommendation_rule_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch feature recommendation rule: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return $this->feature_recommendation_rule_repository->create($attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to create feature recommendation rule: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return $this->feature_recommendation_rule_repository->update($id, $attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to update feature recommendation rule: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return $this->feature_recommendation_rule_repository->delete($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete feature recommendation rule: ' . $e->getMessage());
            throw $e;
        }
    }
}
