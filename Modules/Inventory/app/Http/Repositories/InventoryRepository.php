<?php

namespace Modules\Inventory\app\Http\Repositories;

use Modules\Inventory\app\Models\Inventory;

class InventoryRepository extends BaseRepo
{
    public function __construct(Inventory $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['branches']);
        }

        return $data;
    }

    public function syncBranches(Inventory $inventory, array $branchIds = []): void
    {
        $branchIds = array_values(array_unique(array_filter(
            array_map('intval', $branchIds),
            static fn (int $branchId): bool => $branchId > 0
        )));

        if (empty($branchIds)) {
            $inventory->branches()->detach();
            return;
        }

        $syncData = [];
        foreach ($branchIds as $branchId) {
            $syncData[$branchId] = ['status' => 'active'];
        }

        $inventory->branches()->sync($syncData);
    }

    public function toggleBranchStatus(Inventory $inventory, int $branchId): bool
    {
        $existing = $inventory->branches()
            ->where('branches.id', $branchId)
            ->first();

        if (!$existing) {
            return false;
        }

        $currentStatus = $existing->pivot->status;
        $nextStatus = $currentStatus === 'active' ? 'inactive' : 'active';

        $inventory->branches()->updateExistingPivot($branchId, ['status' => $nextStatus]);

        return true;
    }
}
