<?php

namespace Modules\Product\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Http\Repositories\ProductRepository;
use Modules\Product\app\Models\ProductVariation;

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
            return DB::transaction(function () use ($attributes) {
                $attributes['created_by'] = auth()->id();
                $variations = $attributes['variations'] ?? [];
                unset($attributes['variations']);

                $manualSku = trim((string) ($attributes['sku'] ?? ''));
                if ($manualSku === '') {
                    $attributes['sku'] = 'TMP-SKU-' . uniqid();
                }

                $result = $this->product_repository->create($attributes);

                if ($manualSku === '') {
                    $generatedSku = $this->generateSku((int) $result->id);
                    $result->update(['sku' => $generatedSku]);
                }

                if (is_array($variations) && count($variations) > 0) {
                    $this->syncVariations((int) $result->id, $variations);
                }

                return $result->fresh()->loadMissing(['product_variations.variation']);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create product: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $attributes['updated_by'] = auth()->id();
                $variations = $attributes['variations'] ?? null;
                unset($attributes['variations']);

                if (array_key_exists('sku', $attributes)) {
                    $incomingSku = trim((string) ($attributes['sku'] ?? ''));
                    if ($incomingSku === '') {
                        unset($attributes['sku']);
                    }
                }

                $result = $this->product_repository->update($id, $attributes);
                if (!$result) {
                    return null;
                }

                if (is_array($variations)) {
                    $this->syncVariations($id, $variations);
                }

                return $result->fresh()->loadMissing(['product_variations.variation']);
            });
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

    private function generateSku(int $productId): string
    {
        $prefix = 'PRD-' . str_pad((string) $productId, 6, '0', STR_PAD_LEFT);
        $sku = $prefix;
        $counter = 1;

        while (Product::query()->where('sku', $sku)->exists()) {
            $sku = $prefix . '-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    private function syncVariations(int $productId, array $variations): void
    {
        ProductVariation::query()->where('product_id', $productId)->delete();

        if (count($variations) === 0) {
            return;
        }

        $rows = [];
        $now = now();

        foreach ($variations as $variation) {
            $rows[] = [
                'product_id' => $productId,
                'variation_id' => (int) $variation['variation_id'],
                'variation_value' => (string) $variation['variation_value'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductVariation::query()->insert($rows);
    }
}
