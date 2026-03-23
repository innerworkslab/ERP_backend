<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Http\Repositories\BaseRepo;
use Modules\Inventory\app\Models\UnitOfMeasurementConversion;


class UnitOfMeasurementConversionRepository extends BaseRepo
{
    public function __construct(UnitOfMeasurementConversion $model)
    {
        parent::__construct($model);
    }

    public function toggleActive(UnitOfMeasurementConversion $uom_conversion)
    {
        $uom_conversion->updated_by = auth()->user()->id;
        if ($uom_conversion->status == 'active') {
            $uom_conversion->status = 'inactive';
        } else {
            $uom_conversion->status = 'active';
        }
        $uom_conversion->save();
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
