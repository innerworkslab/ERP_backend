<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\OriginCountry;

class OriginCountryRepository extends BaseRepo
{
    public function __construct(OriginCountry $model)
    {
        parent::__construct($model);
    }
}
