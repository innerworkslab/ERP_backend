<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\Product;

class ProductRepository extends BaseRepo
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load([
                'category',
                'brand',
                'origin_country',
                'stock_uom',
                'purchase_currency',
                'purchase_tax',
                'purchase_uom',
                'sale_currency',
                'sale_tax',
                'sale_uom',
                'created_by',
                'updated_by',
            ]);
        }
        return $data;
    }

    public function toggleActive(Product $product)
    {
        $product->updated_by = auth()->id();
        if ($product->status == 'active') {
            $product->status = 'inactive';
        } else {
            $product->status = 'active';
        }
        $product->save();
    }
}
