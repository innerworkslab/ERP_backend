<?php

namespace Modules\Product\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Product\app\Http\Repositories\CollectionRepository;

class CollectionService
{
    protected $collection_repository;

    public function __construct(CollectionRepository $collection_repository)
    {
        $this->collection_repository = $collection_repository;
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
            $result = $this->collection_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch collection data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->collection_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch collection: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['created_by'] = auth()->id();
            return $this->collection_repository->create($attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to create collection: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->id();
            return $this->collection_repository->update($id, $attributes);
        } catch (Exception $e) {
            logger()->error('Error : Failed to update collection: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return $this->collection_repository->delete($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete collection: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleCollectionStatus($collection)
    {
        $this->collection_repository->toggleActive($collection);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->collection_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find collection with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getProducts(int $collectionId)
    {
        try {
            return $this->collection_repository->getProducts($collectionId);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch collection products: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncProducts(int $collectionId, array $products)
    {
        try {
            return DB::transaction(function () use ($collectionId, $products) {
                $collection = $this->collection_repository->whereFirst('id', $collectionId);
                $collection->updated_by = auth()->id();
                $collection->save();

                return $this->collection_repository->syncProducts($collection, $products);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to sync collection products: ' . $e->getMessage());
            throw $e;
        }
    }
}