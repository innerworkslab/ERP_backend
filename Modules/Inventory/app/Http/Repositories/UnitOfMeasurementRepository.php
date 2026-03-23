<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Http\Repositories\BaseRepo;
use Modules\Inventory\app\Models\UnitOfMeasurement;


class UnitOfMeasurementRepository extends BaseRepo
{
    public function __construct(UnitOfMeasurement $model)
    {
        parent::__construct($model);
    }

    public function toggleActive(UnitOfMeasurement $uom)
    {
        $uom->updated_by = auth()->user()->id;
        if ($uom->status == 'active') {
            $uom->status = 'inactive';
        } else {
            $uom->status = 'active';
        }
        $uom->save();
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
