<?php

namespace Modules\Location\app\Http\Repositories;

use Modules\Location\app\Http\Repositories\BaseRepo;
use Modules\Location\app\Models\State;


class StateRepository extends BaseRepo
{
    public function __construct(State $model)
    {
        parent::__construct($model);
    }
}
