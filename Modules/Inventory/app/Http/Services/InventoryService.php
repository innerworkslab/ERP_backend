<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\app\Http\Repositories\InventoryRepository;

class InventoryService
{
    protected $inventory_repository;

    public function __construct(InventoryRepository $inventory_repository)
    {
        $this->inventory_repository = $inventory_repository;
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
            return $this->inventory_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions,
                whereHas: $whereHas
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch inventory data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->inventory_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch inventory: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return DB::transaction(function () use ($attributes) {
                $branchIds = $this->extractBranchIds($attributes);
                unset($attributes['branch_id'], $attributes['branch_ids']);

                $inventory = $this->inventory_repository->create($attributes);
                $this->inventory_repository->syncBranches($inventory, $branchIds);

                return $this->inventory_repository->find($inventory->id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create inventory: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $branchIds = $this->extractBranchIds($attributes);
                unset($attributes['branch_id'], $attributes['branch_ids']);

                $inventory = $this->inventory_repository->update($id, $attributes);

                if (!$inventory) {
                    return null;
                }
                $this->inventory_repository->syncBranches($inventory, $branchIds);

                return $this->inventory_repository->find($inventory->id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update inventory: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $inventory = $this->inventory_repository->find($id);
                if (!$inventory) {
                    return false;
                }

                $this->inventory_repository->syncBranches($inventory, []);
                return $this->inventory_repository->delete($id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete inventory: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleBranchStatus(int $id, int $branchId): bool
    {
        try {
            return DB::transaction(function () use ($id, $branchId) {
                $inventory = $this->inventory_repository->find($id);
                if (!$inventory) {
                    return false;
                }

                return $this->inventory_repository->toggleBranchStatus($inventory, $branchId);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to toggle branch inventory status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->inventory_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find inventory with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    private function extractBranchIds(array $attributes): array
    {
        if (array_key_exists('branch_ids', $attributes) && is_array($attributes['branch_ids'])) {
            return array_values(array_unique(array_filter(
                array_map('intval', $attributes['branch_ids']),
                static fn (int $branchId): bool => $branchId > 0
            )));
        }

        if (array_key_exists('branch_id', $attributes) && !is_null($attributes['branch_id'])) {
            return [(int) $attributes['branch_id']];
        }

        return [];
    }
}
