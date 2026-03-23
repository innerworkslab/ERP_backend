<?php

namespace Modules\AccessControl\app\Http\Repositories;

use Modules\AccessControl\app\Http\Repositories\BaseRepo;
use Modules\AccessControl\app\Models\Feature;
use Modules\AccessControl\app\Models\Role;


class FeatureRepository extends BaseRepo
{
    public function __construct(Feature $model)
    {
        parent::__construct($model);
    }

    public function getDataWithPaginationAndFilters(
        int $perPage = 10,
        int $page = 1,
        string $orderBy = 'created_at',
        array $searches = null,
        array $conditions = [],
        array $with = [],
        ?array $filters = null,
        ?string $status = null
    ) {
        try {
            $query = $this->model->newQuery();

            if (!empty($with)) {
                $query->with($with);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $query->where($field, $value);
                }
            }

            if (!empty($searches)) {
                $query->where(function ($q) use ($searches) {
                    foreach ($searches as $field => $value) {
                        $q->orWhere($field, 'LIKE', "%{$value}%");
                    }
                });
            }

            if (!empty($filters)) {

                if (!empty($filters['role_id'])) {
                    $query->whereHas('roles', function ($q) use ($filters) {
                        $q->where('roles.id', $filters['role_id']);
                    });
                }

                if (!empty($filters['branch_id'])) {
                    $query->whereHas('roles', function ($q) use ($filters) {
                        $q->where('branch_id', $filters['branch_id']);
                    });
                }

                if (!empty($filters['department_id'])) {
                    $query->whereHas('roles', function ($q) use ($filters) {
                        $q->where('department_id', $filters['department_id']);
                    });
                }
            }

            $totalCount = (clone $query)->count();

            $results = $query
                ->orderBy($orderBy, 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $totalPages = (int) ceil($totalCount / $perPage);

            return [
                'data' => $results,
                'meta' => [
                    'total' => $totalCount,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                ],
            ];

        } catch (\Exception $e) {
            logger()->error('Repo Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleActive(Feature $feature)
    {
        if ($feature->status == 'active') {
            $feature->status = 'inactive';
        } else {
            $feature->status = 'active';
        }
        $feature->save();
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'roles',
                'roles.branch',
                'roles.department',
                'permissions',
            ]);
            $data->role_ids = $data->roles->pluck('id')->toArray();
        }
        return $data;
    }

    public function assignRoles($data)
    {
        $feature = Feature::find($data['feature_id']);
        $roles = $data['role_ids'];
        $feature->roles()->sync($roles);
    }

    public function recommendedFeatures($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return [];
        }

        $assignedFeatureIds = $role->features()->pluck('features.id')->toArray();

        $recommendedFeatures = Feature::whereIn('id', $assignedFeatureIds)->where('status', 'active')->with('permissions')->get();
        return $recommendedFeatures;
    }

    public function otherFeatures($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return [];
        }

        $assignedFeatureIds = $role->features()->pluck('features.id')->toArray();

        $anotherFeatures = Feature::whereNotIn('id', $assignedFeatureIds)->where('status', 'active')->with('permissions')->get();
        return $anotherFeatures;
    }

}
