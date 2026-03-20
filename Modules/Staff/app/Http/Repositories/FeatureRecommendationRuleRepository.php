<?php

namespace Modules\Staff\app\Http\Repositories;

use Modules\Staff\app\Models\StaffFeatureRecommendationRule;

class FeatureRecommendationRuleRepository extends BaseRepo
{
    public function __construct(StaffFeatureRecommendationRule $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        return $this->model
            ->with(['role', 'department', 'feature'])
            ->find($id);
    }

    public function getDataWithPagination(
        $perPage = 10,
        $page = 1,
        $orderBy = 'created_at',
        $searches = null,
        $conditions = [],
        $orConditions = [],
        $with = [],
        $whereHas = null,
        $status = null
    ) {
        return parent::getDataWithPagination(
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
    }
}
