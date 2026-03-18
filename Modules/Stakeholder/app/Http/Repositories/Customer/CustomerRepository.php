<?php

namespace Modules\Stakeholder\app\Http\Repositories\Customer;

use Modules\Stakeholder\app\Http\Repositories\BaseRepo;
use Modules\Stakeholder\app\Models\Customer;

class CustomerRepository extends BaseRepo
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }
}