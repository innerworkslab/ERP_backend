<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\Category;

class CategoryRepository extends BaseRepo
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['created_by', 'updated_by']);
        }
        return $data;
    }

    public function toggleActive(Category $category)
    {
        $category->updated_by = auth()->user()->id;
        if ($category->status == 'active') {
            $category->status = 'inactive';
        } else {
            $category->status = 'active';
        }
        $category->save();
    }
}