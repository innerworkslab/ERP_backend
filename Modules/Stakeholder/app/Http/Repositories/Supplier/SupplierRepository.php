<?php

namespace Modules\Stakeholder\app\Http\Repositories\Supplier;

use Modules\Stakeholder\app\Http\Repositories\BaseRepo;
use Modules\Stakeholder\app\Models\Supplier;

class SupplierRepository extends BaseRepo
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }
}