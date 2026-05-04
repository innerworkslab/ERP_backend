<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Http\Repositories\BaseRepo;
use Modules\Product\app\Models\Variation;


class VariationRepository extends BaseRepo
{
    public function __construct(Variation $model)
    {
        parent::__construct($model);
    }

    public function create($data)
    {
        $result = $this->model->create($data);
        $result->productCategories()->sync($data['product_category_ids']);
        return $result;
    }

    public function update($id, array $data)
    {
        $variation = $this->model->find($id);
        $variation->update($data);
        $variation->productCategories()->sync($data['product_category_ids']);
        return $variation;
    }

    public function toggleActive(Variation $variation)
    {
        $variation->updated_by = auth()->user()->id;
        if ($variation->status == 'active') {
            $variation->status = 'inactive';
        } else {
            $variation->status = 'active';
        }
        $variation->save();
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['created_by', 'updated_by', 'productCategories']);
        }
        return $data;
    }
}
