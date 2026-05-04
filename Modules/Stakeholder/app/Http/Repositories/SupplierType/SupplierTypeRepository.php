<?php

namespace Modules\Stakeholder\app\Http\Repositories\SupplierType;

use Modules\Stakeholder\app\Http\Repositories\BaseRepo;
use Modules\Stakeholder\app\Models\SupplierType;

class SupplierTypeRepository extends BaseRepo
{
    public function __construct(SupplierType $model)
    {
        parent::__construct($model);
    }
}
