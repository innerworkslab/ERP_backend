<?php

namespace Modules\PriceGroup\app\Http\Repositories\SellingPriceGroup;

use Modules\PriceGroup\app\Http\Repositories\BaseRepo;
use Modules\PriceGroup\app\Models\SellingPriceGroup;

class SellingPriceGroupRepository extends BaseRepo
{
    public function __construct(SellingPriceGroup $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['customer_type', 'branch']);
        }
        return $data;
    }

    public function toggleActive(SellingPriceGroup $sellingPriceGroup): void
    {
        $sellingPriceGroup->is_active = !$sellingPriceGroup->is_active;
        $sellingPriceGroup->save();
    }
}
