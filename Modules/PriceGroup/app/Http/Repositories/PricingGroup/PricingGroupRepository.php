<?php

namespace Modules\PriceGroup\app\Http\Repositories\PricingGroup;

use Modules\PriceGroup\app\Http\Repositories\BaseRepo;
use Modules\PriceGroup\app\Models\PricingGroup;

class PricingGroupRepository extends BaseRepo
{
    public function __construct(PricingGroup $model)
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

    public function toggleActive(PricingGroup $pricingGroup): void
    {
        $pricingGroup->is_active = !$pricingGroup->is_active;
        $pricingGroup->save();
    }
}
