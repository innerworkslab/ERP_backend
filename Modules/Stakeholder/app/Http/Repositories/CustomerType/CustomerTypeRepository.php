<?php

namespace Modules\Stakeholder\app\Http\Repositories\CustomerType;

use Modules\Stakeholder\app\Http\Repositories\BaseRepo;
use Modules\Stakeholder\app\Models\CustomerType;

class CustomerTypeRepository extends BaseRepo
{
    public function __construct(CustomerType $model)
    {
        parent::__construct($model);
    }
}
