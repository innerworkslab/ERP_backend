<?php

namespace Modules\Inventory\app\Http\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\app\Models\StockMovement;


class StockLedgerService
{
    protected $stock_movement;

    public function __construct(StockMovement $stock_movement)
    {
        $this->stock_movement = $stock_movement;
    }

    public function addStockLedger(array $attributes): StockMovement
    {
        $attributes = $this->normalizeAttributes($attributes);
        $balances = $this->getRunningBalances($attributes['sku'], $attributes['inventory_name']);

        $quantityDelta = (float) $attributes['quantity'];
        $costDelta = (float) $attributes['total_cost'];

        $balanceQuantityAfter = $balances['quantity_before'] + $quantityDelta;
        $balanceCostAfter = $balances['cost_before'] + $costDelta;

        return $this->stock_movement->create($attributes + [
            'balance_quantity_before' => $balances['quantity_before'],
            'balance_quantity_after' => $balanceQuantityAfter,
            'balance_cost_before' => $balances['cost_before'],
            'balance_cost_after' => $balanceCostAfter,
        ]);
    }

    public function addBulkStockLedger(array $rows): void
    {
        foreach ($rows as $row) {
            $this->addStockLedger($row);
        }
    }

    public function clearByReference(string $referenceType, ?int $referenceId = null, ?string $voucherNo = null): void
    {
        $query = $this->stock_movement->newQuery()->where('reference_type', $referenceType);

        if ($referenceId) {
            $query->where('reference_id', $referenceId);
        } elseif (!empty($voucherNo)) {
            $query->where('voucher_no', $voucherNo);
        } else {
            return;
        }

        $query->delete();
    }

    public function resolveBranchName(?Model $inventory): string
    {
        if (!$inventory || !$inventory->relationLoaded('branches')) {
            return '-';
        }

        $activeBranches = $inventory->branches
            ->filter(function ($branch) {
                return !isset($branch->pivot->status) || $branch->pivot->status === 'active';
            })->pluck('name')->filter();

        if ($activeBranches->isNotEmpty()) {
            return $activeBranches->implode(', ');
        }

        $allBranches = $inventory->branches->pluck('name')->filter();
        return $allBranches->implode(', ') ?: '-';
    }

    protected function normalizeAttributes(array $attributes): array
    {
        $quantity = abs((float) ($attributes['quantity'] ?? 0));
        $unitCost = (float) ($attributes['unit_cost'] ?? 0);
        $movementType = $attributes['movement_type'] ?? 'in';
        $sign = $movementType === 'out' ? -1 : 1;

        $attributes['reference_id'] = $attributes['reference_id'] ?? null;
        $attributes['voucher_no'] = $attributes['voucher_no'] ?? null;
        $attributes['branch_name'] = $attributes['branch_name'] ?? '-';
        $attributes['quantity'] = $quantity * $sign;
        $attributes['unit_cost'] = $unitCost;
        $baseTotalCost = array_key_exists('total_cost', $attributes)
            ? abs((float) $attributes['total_cost'])
            : $quantity * $unitCost;
        $attributes['total_cost'] = $baseTotalCost * $sign;

        return $attributes;
    }

    protected function getRunningBalances(string $sku, string $inventoryName): array
    {
        $movements = $this->stock_movement->newQuery()
            ->where('sku', $sku)
            ->where('inventory_name', $inventoryName)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['movement_type', 'quantity', 'total_cost']);

        $quantityBefore = 0.0;
        $costBefore = 0.0;

        foreach ($movements as $movement) {
            $qty = (float) ($movement->quantity ?? 0);
            $cost = (float) ($movement->total_cost ?? 0);

            $quantityBefore += $qty;
            $costBefore += $cost;
        }

        return [
            'quantity_before' => $quantityBefore,
            'cost_before' => $costBefore,
        ];

    }
}