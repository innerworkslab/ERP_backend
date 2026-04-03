<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\app\Http\Repositories\OpeningStockRepository;

class OpeningStockService
{
    protected $opening_stock_repository;

    public function __construct(OpeningStockRepository $opening_stock_repository)
    {
        $this->opening_stock_repository = $opening_stock_repository;
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
            $queryConditions = $conditions;
            unset($queryConditions['voucher_date_from'], $queryConditions['voucher_date_to']);

            if (!empty($status)) {
                $queryConditions['status'] = $status;
            }

            $result = $this->opening_stock_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                status: null,
                searches: $searches,
                with: ['inventory', 'created_by', 'updated_by', 'confirmed_by'],
                conditions: $queryConditions,
                whereHas: $whereHas
            );

            if (!empty($conditions['voucher_date_from']) || !empty($conditions['voucher_date_to'])) {
                $result['data'] = $result['data']->filter(function ($item) use ($conditions) {
                    $from = $conditions['voucher_date_from'] ?? null;
                    $to = $conditions['voucher_date_to'] ?? null;

                    if ($from && $item->voucher_date < $from) {
                        return false;
                    }

                    if ($to && $item->voucher_date > $to) {
                        return false;
                    }

                    return true;
                })->values();
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch opening stock data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->opening_stock_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch opening stock: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return DB::transaction(function () use ($attributes) {
                $attributes['voucher_no'] = $this->VocherNoGenerate();
                $attributes['voucher_date'] = now()->toDateString();
                $attributes['created_by'] = auth()->user()->id;

                $this->opening_stock_repository->create($attributes);

                return true;
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create opening stock: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $existing = $this->opening_stock_repository->find($id);

                if (!$existing) {
                    return null;
                }

                if ($existing->status === 'confirmed') {
                    throw new \RuntimeException('Confirmed opening stock cannot be updated');
                }

                $attributes['updated_by'] = auth()->user()->id;

                $openingStock = $this->opening_stock_repository->updateWithLines($id, $attributes);

                if (!$openingStock) {
                    return null;
                }

                return true;
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update opening stock: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $existing = $this->opening_stock_repository->find($id);

            if (!$existing) {
                return false;
            }

            if ($existing->status === 'confirmed') {
                throw new \RuntimeException('Confirmed opening stock cannot be deleted');
            }

            return $this->opening_stock_repository->delete($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete opening stock: ' . $e->getMessage());
            throw $e;
        }
    }

    public function confirm(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $existing = $this->opening_stock_repository->find($id);

                if (!$existing) {
                    return null;
                }

                if ($existing->status === 'confirmed') {
                    throw new \RuntimeException('Opening stock already confirmed');
                }

                $result = $this->opening_stock_repository->update($id, [
                    'status' => 'confirmed',
                    'confirmed_by' => auth()->user()->id,
                    'confirmed_at' => now(),
                ]);

                if (!$result) {
                    return null;
                }

                return $this->opening_stock_repository->find($id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to confirm opening stock: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->opening_stock_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find opening stock with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    //format INW-OS-26-4-2-0001
    private function VocherNoGenerate()
    {
        $lastRecord = $this->opening_stock_repository->getLastRecord();
        $lastId = $lastRecord ? (int) substr($lastRecord->voucher_no, strrpos($lastRecord->voucher_no, '-') + 1) : 0;
        $newId = $lastId + 1;
        $datePart = date('y-n-j');
        return 'INW-OS-' . $datePart . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
    }
}
