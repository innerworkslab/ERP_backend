<?php

namespace Modules\PriceGroup\app\Http\Services\SellingPriceGroup;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\PriceGroup\app\Http\Repositories\SellingPriceGroup\SellingPriceGroupRepository;
use Modules\PriceGroup\app\Models\SellingPriceGroup;

class SellingPriceGroupService
{
    public function __construct(private SellingPriceGroupRepository $sellingPriceGroupRepository)
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

        return $this->sellingPriceGroupRepository->getDataWithPagination(
            $perPage,
            $page,
            'created_at',
            $searches,
            $conditions,
            [],
            ['customer_type', 'branch']
        );
    }

    public function create(array $attributes): SellingPriceGroup
    {
        $created = $this->sellingPriceGroupRepository->create($attributes);

        return $this->sellingPriceGroupRepository->find($created->id);
    }

    public function findOrFail(int $id): SellingPriceGroup
    {
        $model = $this->sellingPriceGroupRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Selling price group not found.');
        }

        return $model;
    }

    public function update(int $id, array $attributes): ?SellingPriceGroup
    {
        $model = $this->sellingPriceGroupRepository->find($id);

        if (!$model) {
            return null;
        }

        $this->sellingPriceGroupRepository->update($id, $attributes);

        return $this->sellingPriceGroupRepository->find($id);
    }

    public function delete(int $id): bool
    {
        return $this->sellingPriceGroupRepository->delete($id);
    }

    public function toggleActive(int $id): ?SellingPriceGroup
    {
        $model = $this->sellingPriceGroupRepository->find($id);

        if (!$model) {
            return null;
        }

        $this->sellingPriceGroupRepository->toggleActive($model);

        return $this->sellingPriceGroupRepository->find($id);
    }
}
