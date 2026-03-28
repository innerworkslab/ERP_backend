<?php

namespace Modules\Location\app\Http\Services;

use Exception;
use Modules\Location\app\Http\Repositories\CityRepository;

class CityService
{
    protected $city_repository;

    public function __construct(CityRepository $city_repository)
    {
        $this->city_repository = $city_repository;
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
            $result = $this->city_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch city data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }
}
