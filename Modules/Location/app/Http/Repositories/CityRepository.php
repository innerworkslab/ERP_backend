<?php

namespace Modules\Location\app\Http\Repositories;

use Modules\Location\app\Http\Repositories\BaseRepo;
use Modules\Location\app\Models\City;


class CityRepository extends BaseRepo
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }
}
