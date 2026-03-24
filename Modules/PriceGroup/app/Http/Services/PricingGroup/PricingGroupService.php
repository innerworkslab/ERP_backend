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
        $perPage = (int) ($filters['per_page'] ?? 10);
        $page = (int) ($filters['page'] ?? 1);

        $searches = !empty($filters['keyword'])
            ? ['name' => $filters['keyword']]
            : null;

        $conditions = [];

        if (!empty($filters['customer_type_id'])) {
            $conditions['customer_type_id'] = $filters['customer_type_id'];
        }

        if (!empty($filters['branch_id'])) {
            $conditions['branch_id'] = $filters['branch_id'];
        }

        if (array_key_exists('is_active', $filters)) {
            $conditions['is_active'] = (bool) $filters['is_active'];
        }

        return $this->pricingGroupRepository->getDataWithPagination(
            $perPage,
            $page,
            'created_at',
            $searches,
            $conditions,
            [],
            ['customer_type', 'branch']
        );
    }

    public function create(array $attributes): PricingGroup
    {
        $created = $this->pricingGroupRepository->create($attributes);

        return $this->pricingGroupRepository->find($created->id);
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
        $model = $this->pricingGroupRepository->find($id);

        if (!$model) {
            return null;
        }

        $this->pricingGroupRepository->update($id, $attributes);

        return $this->pricingGroupRepository->find($id);
    }

    public function delete(int $id): bool
    {
        return $this->pricingGroupRepository->delete($id);
    }

    public function toggleActive(int $id): ?PricingGroup
    {
        $model = $this->pricingGroupRepository->find($id);

        if (!$model) {
            return null;
        }

        $this->pricingGroupRepository->toggleActive($model);

        return $this->pricingGroupRepository->find($id);
    }
}
