<?php

namespace Modules\PriceGroup\app\Http\Services\SellingPriceGroup;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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

        $whereHas = null;
        if (!empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
            $whereHas = [
                'branches' => function ($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                }
            ];
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
            ['customer_type', 'branches'],
            $whereHas
        );
    }

    public function create(array $attributes): SellingPriceGroup
    {
        return DB::transaction(function () use ($attributes) {
            $branchIds = $this->extractBranchIds($attributes);
            unset($attributes['branch_id']);

            $created = $this->sellingPriceGroupRepository->create($attributes);
            $this->sellingPriceGroupRepository->syncBranches($created, $branchIds);

            return $this->sellingPriceGroupRepository->find($created->id);
        });
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
        return DB::transaction(function () use ($id, $attributes) {
            $model = $this->sellingPriceGroupRepository->find($id);

            if (!$model) {
                return null;
            }

            $hasBranchPayload = array_key_exists('branch_id', $attributes);
            $branchIds = $this->extractBranchIds($attributes);
            unset($attributes['branch_id']);

            $this->sellingPriceGroupRepository->update($id, $attributes);

            if ($hasBranchPayload) {
                $this->sellingPriceGroupRepository->syncBranches($model, $branchIds);
            }

            return $this->sellingPriceGroupRepository->find($id);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $model = $this->sellingPriceGroupRepository->find($id);

            if (!$model) {
                return false;
            }

            $this->sellingPriceGroupRepository->syncBranches($model, []);
            return $this->sellingPriceGroupRepository->delete($id);
        });
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

    private function extractBranchIds(array $attributes): array
    {
        if (!array_key_exists('branch_id', $attributes)) {
            return [];
        }

        if (is_array($attributes['branch_id'])) {
            return array_values(array_unique(array_filter(
                array_map('intval', $attributes['branch_id']),
                static fn (int $branchId): bool => $branchId > 0
            )));
        }

        if (!is_null($attributes['branch_id'])) {
            return [(int) $attributes['branch_id']];
        }

        return [];
    }
}
