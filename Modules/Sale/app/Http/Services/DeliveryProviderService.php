<?php

namespace Modules\Sale\app\Http\Services;

use Exception;
use Modules\Sale\app\Http\Repositories\DeliveryProviderRepository;
use Modules\Sale\app\Models\DeliveryProvider;

class DeliveryProviderService
{
    protected $delivery_provider_repository;

    public function __construct(DeliveryProviderRepository $delivery_provider_repository)
    {
        $this->delivery_provider_repository = $delivery_provider_repository;
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
            return $this->delivery_provider_repository->getDataWithPagination(
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
            logger()->error('Error : Failed to fetch delivery provider data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->delivery_provider_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch delivery provider: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['status'] = $attributes['status'] ?? 'active';
            $attributes['created_by'] = auth()->user()->id;

            return $this->delivery_provider_repository->create($attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to create delivery provider: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->user()->id;

            return $this->delivery_provider_repository->update($id, $attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to update delivery provider: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return $this->delivery_provider_repository->delete($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete delivery provider: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleDeliveryProviderStatus(DeliveryProvider $delivery_provider)
    {
        $this->delivery_provider_repository->toggleActive($delivery_provider);
    }
}
