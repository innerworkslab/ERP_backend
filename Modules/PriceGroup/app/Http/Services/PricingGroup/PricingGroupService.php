<?php

namespace Modules\PriceGroup\app\Http\Services\PricingGroup;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\PriceGroup\app\Http\Repositories\PricingGroup\PricingGroupRepository;
use Modules\PriceGroup\app\Models\PricingGroup;

class PricingGroupService
{
    public function __construct(private PricingGroupRepository $pricingGroupRepository)
    {
    }

    public function list(array $filters): array
    {
        return $this->pricingGroupRepository->listWithPaginationAndFilters($filters);
    }

    public function create(array $attributes): PricingGroup
    {
        return $this->pricingGroupRepository->create($attributes);
    }

    public function findOrFail(int $id): PricingGroup
    {
        $model = $this->pricingGroupRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Price group not found.');
        }

        return $model;
    }

    public function update(int $id, array $attributes): ?PricingGroup
    {
        return $this->pricingGroupRepository->update($id, $attributes);
    }

    public function delete(int $id): bool
    {
        return $this->pricingGroupRepository->delete($id);
    }

    public function toggleActive(int $id): ?PricingGroup
    {
        return $this->pricingGroupRepository->toggleActive($id);
    }
}
