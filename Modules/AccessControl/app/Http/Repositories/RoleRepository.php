<?php

namespace Modules\AccessControl\app\Http\Repositories;

use Modules\AccessControl\app\Http\Repositories\BaseRepo;
use Modules\AccessControl\app\Models\Role;


class RoleRepository extends BaseRepo
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function toggleActive(Role $role)
    {
        $role->updated_by = auth()->user()->id;
        if ($role->status == 'active') {
            $role->status = 'inactive';
        } else {
            $role->status = 'active';
        }
        $role->save();
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['parentRole', 'children', 'department', 'features', 'created_by', 'updated_by']);
        }
        return $data;
    }

    public function getRolesWithoutPagination(
        $status,
        $searches,
        $with,
        $conditions
    ) {

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

        return $query
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
