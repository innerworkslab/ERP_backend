<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\Brand;

class BrandRepository extends BaseRepo
{
    public function __construct(Brand $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['created_by', 'updated_by']);
        }
        return $data;
    }

    public function toggleActive(Brand $brand)
    {
        $brand->updated_by = auth()->user()->id;
        if ($brand->status == 'active') {
            $brand->status = 'inactive';
        } else {
            $brand->status = 'active';
        }
        $brand->save();
    }
}