<?php

namespace Modules\Staff\app\Http\Repositories\Nrc;

use Illuminate\Support\Collection;
use Modules\Staff\app\Http\Repositories\BaseRepo;
use Modules\Staff\app\Models\NrcTownship;

class NrcRepository extends BaseRepo
{
    public function __construct(NrcTownship $model)
    {
        parent::__construct($model);
    }

    public function getTownshipsByNrcCode(int $nrcCode): Collection
    {
        return $this->model->query()
            ->where('nrc_code', (string) $nrcCode)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_mm', 'nrc_code']);
    }
}
