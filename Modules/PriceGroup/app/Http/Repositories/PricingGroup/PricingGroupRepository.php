<?php

namespace Modules\PriceGroup\app\Http\Repositories\PricingGroup;

use Illuminate\Database\Eloquent\Collection;
use Modules\PriceGroup\app\Models\PricingGroup;

class PricingGroupRepository
{
    public function __construct(private PricingGroup $model)
    {
    }

    public function all(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function listWithPaginationAndFilters(array $filters): array
    {
        $query = $this->model->newQuery()->with(['customer_type', 'branch'])->latest();

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where('name', 'like', "%{$keyword}%");
        }

        if (!empty($filters['customer_type_id'])) {
            $query->where('customer_type_id', $filters['customer_type_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);
        $page = (int) ($filters['page'] ?? 1);
        $totalCount = (clone $query)->count();

        $results = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($totalCount / $perPage),
            ],
        ];
    }

    public function create(array $attributes): PricingGroup
    {
        $model = $this->model->newQuery()->create($attributes);

        return $model->fresh(['customer_type', 'branch']);
    }

    public function find(int $id): ?PricingGroup
    {
        return $this->model->newQuery()->with(['customer_type', 'branch'])->find($id);
    }

    public function update(int $id, array $attributes): ?PricingGroup
    {
        $model = $this->find($id);

        if (!$model) {
            return null;
        }

        $model->update($attributes);

        return $model->fresh(['customer_type', 'branch']);
    }

    public function delete(int $id): bool
    {
        $model = $this->find($id);

        if (!$model) {
            return false;
        }

        return (bool) $model->delete();
    }

    public function toggleActive(int $id): ?PricingGroup
    {
        $model = $this->find($id);

        if (!$model) {
            return null;
        }

        $model->is_active = !$model->is_active;
        $model->save();

        return $model->fresh(['customer_type', 'branch']);
    }
}
