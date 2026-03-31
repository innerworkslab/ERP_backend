<?php

namespace Modules\Product\app\Http\Repositories;

use Modules\Product\app\Models\Collection;

class CollectionRepository extends BaseRepo
{
    public function __construct(Collection $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->withCount(['products as product_count'])->find($id);
        if ($data) {
            $data->load($this->relations());
        }

        return $data;
    }

    public function getDataWithPagination($perPage = 10, $page = 1, $orderBy = 'created_at', $searches = null, $conditions = [], $orConditions = [], $with = [], $whereHas = null, $status = null)
    {
        $query = $this->model->query()->withCount(['products as product_count']);

        if (count($with) > 0) {
            $query->with($with);
        }

        if ($whereHas && is_array($whereHas)) {
            foreach ($whereHas as $relation => $constraint) {
                $query->whereHas($relation, $constraint);
            }
        }

        $offset = $perPage * ($page - 1);

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        $query->limit($perPage);

        if (!empty($status)) {
            if ($status === 'inactive') {
                $query->where('status', 'inactive');
            } elseif ($status === 'active') {
                $query->where('status', 'active');
            } elseif ($status === 'closed') {
                $query->where('status', 'closed');
            } elseif ($status === 'open') {
                $query->where('status', 'open');
            }
        }

        if ($searches) {
            $query->where(function ($q) use ($searches) {
                foreach ($searches as $key => $value) {
                    $q->orWhere($key, 'LIKE', "%$value%");
                }
            });
        }

        foreach ($conditions as $key => $condition) {
            $query->where($key, $condition);
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }

        $totalCount = $query->count();

        if ($offset > 0) {
            $query->offset($offset);
        }

        $totalPages = ceil($totalCount / $perPage);
        $results = $query->latest()->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function getProducts(int $collectionId)
    {
        $collection = $this->model->find($collectionId);
        if (!$collection) {
            return null;
        }

        return $collection->products()->with($this->productRelations())->get();
    }

    public function syncProducts(Collection $collection, array $products): Collection
    {
        $syncData = [];

        foreach ($products as $product) {
            $syncData[$product['product_id']] = [
                'product_qty' => $product['product_qty'],
            ];
        }

        $collection->products()->sync($syncData);

        return $this->find($collection->id);
    }

    public function toggleActive(Collection $collection)
    {
        $collection->updated_by = auth()->id();
        if ($collection->status == 'active') {
            $collection->status = 'inactive';
        } else {
            $collection->status = 'active';
        }

        $collection->save();
    }

    private function relations(): array
    {
        return [
            'purchase_currency',
            'purchase_tax',
            'purchase_uom',
            'sale_currency',
            'sale_tax',
            'sale_uom',
            'created_by',
            'updated_by',
            'products.category',
            'products.brand',
            'products.origin_country',
            'products.purchase_currency',
            'products.purchase_tax',
            'products.purchase_uom',
            'products.sale_currency',
            'products.sale_tax',
            'products.sale_uom',
        ];
    }

    private function productRelations(): array
    {
        return [
            'category',
            'brand',
            'origin_country',
            'purchase_currency',
            'purchase_tax',
            'purchase_uom',
            'sale_currency',
            'sale_tax',
            'sale_uom',
        ];
    }
}