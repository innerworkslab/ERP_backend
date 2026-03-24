<?php

namespace Modules\PriceGroup\app\Http\Repositories\DiscountGroup;

use Modules\PriceGroup\app\Http\Repositories\BaseRepo;
use Modules\PriceGroup\app\Models\DiscountGroup;

class DiscountGroupRepository extends BaseRepo
{
    public function __construct(DiscountGroup $model)
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

    public function toggleActive(DiscountGroup $discountGroup): void
    {
        $discountGroup->is_active = !$discountGroup->is_active;
        $discountGroup->save();
    }
}
