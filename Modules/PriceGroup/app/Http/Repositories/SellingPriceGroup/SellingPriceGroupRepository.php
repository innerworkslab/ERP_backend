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
            $data->load(['customer_type', 'branches']);
        }
        return $data;
    }

    public function syncBranches(SellingPriceGroup $sellingPriceGroup, array $branchIds = []): void
    {
        $branchIds = array_values(array_unique(array_filter(
            array_map('intval', $branchIds),
            static fn (int $branchId): bool => $branchId > 0
        )));

        $sellingPriceGroup->branches()->sync($branchIds);
    }

    public function toggleActive(SellingPriceGroup $sellingPriceGroup): void
    {
        $sellingPriceGroup->status = $sellingPriceGroup->status === 'active' ? 'inactive' : 'active';
        $sellingPriceGroup->save();
    }
}
