<?php

namespace Modules\Product\app\Http\Services;

use Exception;
use Modules\Product\app\Http\Repositories\ProductRepository;

class ProductService
{
    protected $product_repository;

    public function __construct(ProductRepository $product_repository)
    {
        $this->product_repository = $product_repository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        string $orderBy = 'created_at',
        array $searches = null,
        array $conditions = [],
        array $orConditions = [],
        array $with = [],
        ?array $whereHas = null,
        ?string $status = null
    ) {
        try {
            $perPage = max(1, $perPage);
            $page = max(1, $page);

            $result = $this->product_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch product data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->product_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch product: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['created_by'] = auth()->id();
            $result = $this->product_repository->create($attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create product: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->id();
            $result = $this->product_repository->update($id, $attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to update product: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->product_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete product: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleProductStatus($data)
    {
        $this->product_repository->toggleActive($data);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->product_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find product with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
