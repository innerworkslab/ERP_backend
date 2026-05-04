<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\OriginCountry;

class OriginCountryRepository extends BaseRepo
{
    public function __construct(OriginCountry $model)
    {
        parent::__construct($model);
    }

    public function toggleActive(OriginCountry $originCountry): void
    {
        try {
            $originCountry->status = $originCountry->status === 'active' ? 'inactive' : 'active';
            $originCountry->save();
        } catch (\Exception $e) {
            // Handle exception, e.g., log the error or rethrow
            throw $e;
        }
    }
}
