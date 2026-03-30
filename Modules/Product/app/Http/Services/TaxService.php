<?php

namespace Modules\Product\app\Http\Services;

use Exception;
use Modules\Product\app\Models\Tax;
use Modules\Product\app\Http\Repositories\TaxRepository;

class TaxService
{
    private const CODE_PREFIX = 'TAX-';
    private const CODE_LENGTH = 5;

    protected $tax_repository;

    public function __construct(TaxRepository $tax_repository)
    {
        $this->tax_repository = $tax_repository;
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
            $result = $this->tax_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch tax data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->tax_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch tax: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['code'] = $this->resolveCode($attributes['code'] ?? null);
            $attributes['created_by'] = auth()->user()->id;
            $result = $this->tax_repository->create($attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create tax: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->user()->id;
            $result = $this->tax_repository->update($id, $attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to update tax: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->tax_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete tax: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleTaxStatus($data)
    {
        $this->tax_repository->toggleActive($data);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->tax_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find tax with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    private function resolveCode(?string $manualCode): string
    {
        $manualCode = is_string($manualCode) ? trim($manualCode) : null;
        if (!empty($manualCode)) {
            return $manualCode;
        }

        return $this->generateTaxCode();
    }

    private function generateTaxCode(): string
    {
        $nextNumber = (int) Tax::count() + 1;

        do {
            $code = self::CODE_PREFIX . str_pad((string) $nextNumber, self::CODE_LENGTH, '0', STR_PAD_LEFT);
            $exists = Tax::where('code', $code)->exists();
            $nextNumber++;
        } while ($exists);

        return $code;
    }
}
