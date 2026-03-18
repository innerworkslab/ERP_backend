<?php

namespace Modules\Organization\app\Http\Repositories;

use Modules\Organization\app\Http\Repositories\BaseRepo;
use Modules\Organization\app\Models\Department;


class DepartmentRepository extends BaseRepo
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    public function toggleActive(Department $department)
    {
        if ($department->status == 'active') {
            $department->status = 'inactive';
        } else {
            $department->status = 'active';
        }
        $department->save();
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['branch']);
        }
        return $data;
    }
}
