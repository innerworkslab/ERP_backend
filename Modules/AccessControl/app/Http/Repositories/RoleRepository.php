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
            $data->load(['parentRole', 'children', 'branch', 'department', 'features', 'created_by', 'updated_by']);
        }
        return $data;
    }
}
