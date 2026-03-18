<?php

namespace Modules\Organization\app\Http\Repositories;

use Modules\Organization\app\Http\Repositories\BaseRepo;
use Modules\Organization\app\Models\Branch;


class BranchRepository extends BaseRepo
{
    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }

    public function toggleActive(Branch $branch)
    {
        $branch->updated_by = auth()->user()->id;
        if ($branch->status == 'active') {
            $branch->status = 'inactive';
        } else {
            $branch->status = 'active';
        }
        $branch->save();
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['created_by', 'updated_by']);
        }
        return $data;
    }
}
