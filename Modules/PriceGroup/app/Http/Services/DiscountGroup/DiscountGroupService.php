<?php

namespace Modules\PriceGroup\app\Http\Services\DiscountGroup;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\PriceGroup\app\Http\Repositories\DiscountGroup\DiscountGroupRepository;
use Modules\PriceGroup\app\Models\DiscountGroup;

class DiscountGroupService
{
    public function __construct(private DiscountGroupRepository $discountGroupRepository)
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

        return $this->discountGroupRepository->getDataWithPagination(
            $perPage,
            $page,
            'created_at',
            $searches,
            $conditions,
            [],
            ['customer_type', 'branch']
        );
    }

    public function create(array $attributes): DiscountGroup
    {
        $created = $this->discountGroupRepository->create($attributes);

        return $this->discountGroupRepository->find($created->id);
    }

    public function findOrFail(int $id): DiscountGroup
    {
        $model = $this->discountGroupRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Discount group not found.');
        }

        return $model;
    }

    public function update(int $id, array $attributes): ?DiscountGroup
    {
        $model = $this->discountGroupRepository->find($id);

        if (!$model) {
            return null;
        }

        $this->discountGroupRepository->update($id, $attributes);

        return $this->discountGroupRepository->find($id);
    }

    public function delete(int $id): bool
    {
        return $this->discountGroupRepository->delete($id);
    }

    public function toggleActive(int $id): ?DiscountGroup
    {
        $model = $this->discountGroupRepository->find($id);

        if (!$model) {
            return null;
        }

        $this->discountGroupRepository->toggleActive($model);

        return $this->discountGroupRepository->find($id);
    }
}
