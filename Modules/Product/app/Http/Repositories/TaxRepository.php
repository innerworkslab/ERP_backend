<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\Tax;

class TaxRepository extends BaseRepo
{
    public function __construct(Tax $model)
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

    public function toggleActive(Tax $tax)
    {
        $tax->updated_by = auth()->user()->id;
        if ($tax->status == 'active') {
            $tax->status = 'inactive';
        } else {
            $tax->status = 'active';
        }
        $tax->save();
    }
}
