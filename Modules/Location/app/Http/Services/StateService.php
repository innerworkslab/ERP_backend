<?php

namespace Modules\Location\app\Http\Services;

use Exception;
use Modules\Location\app\Http\Repositories\StateRepository;

class StateService
{
    protected $state_repository;

    public function __construct(StateRepository $state_repository)
    {
        $this->state_repository = $state_repository;
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
            $result = $this->state_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch state data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }
}
