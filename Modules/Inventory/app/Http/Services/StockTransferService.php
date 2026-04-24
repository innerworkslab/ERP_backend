<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\app\Http\Repositories\StockTransferRepository;

class StockTransferService
{
    protected $stock_transfer_repository;
    protected $stock_ledger_service;
    protected $uom_conversion_service;

    public function __construct(
        StockTransferRepository $stock_transfer_repository,
        StockLedgerService $stock_ledger_service,
        UOMConversionService $uom_conversion_service
    )
    {
        $this->stock_transfer_repository = $stock_transfer_repository;
        $this->stock_ledger_service = $stock_ledger_service;
        $this->uom_conversion_service = $uom_conversion_service;
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
            unset($queryConditions['transfer_date_from'], $queryConditions['transfer_date_to']);

            if (!empty($status)) {
                $queryConditions['status'] = $status;
            }

            $result = $this->stock_transfer_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                status: null,
                searches: $searches,
                with: ['source_inventory', 'target_inventory', 'created_by', 'updated_by', 'confirmed_by', 'rejected_by'],
                conditions: $queryConditions,
                whereHas: $whereHas
            );

            if (!empty($conditions['transfer_date_from']) || !empty($conditions['transfer_date_to'])) {
                $result['data'] = $result['data']->filter(function ($item) use ($conditions) {
                    $from = $conditions['transfer_date_from'] ?? null;
                    $to = $conditions['transfer_date_to'] ?? null;

                    if ($from && $item->transfer_date < $from) {
                        return false;
                    }

                    if ($to && $item->transfer_date > $to) {
                        return false;
                    }

                    return true;
                })->values();
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch stock transfer data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->stock_transfer_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch stock transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return DB::transaction(function () use ($attributes) {
                $attributes['reference_id'] = $this->referenceIdGenerate();
                $attributes['created_by'] = auth()->user()->id;

                $this->stock_transfer_repository->create($attributes);

                return true;
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create stock transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $existing = $this->stock_transfer_repository->find($id);

                if (!$existing) {
                    return null;
                }

                if ($existing->status !== 'pending') {
                    throw new \RuntimeException('Only pending stock transfer can be updated');
                }

                $attributes['updated_by'] = auth()->user()->id;

                $stockTransfer = $this->stock_transfer_repository->updateWithLines($id, $attributes);

                if (!$stockTransfer) {
                    return null;
                }

                return true;
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update stock transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $existing = $this->stock_transfer_repository->find($id);

            if (!$existing) {
                return false;
            }

            if ($existing->status !== 'pending') {
                throw new \RuntimeException('Only pending stock transfer can be deleted');
            }

            return $this->stock_transfer_repository->delete($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete stock transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function confirm(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $existing = $this->stock_transfer_repository->find($id);

                if (!$existing) {
                    return null;
                }

                if ($existing->status === 'confirmed') {
                    throw new \RuntimeException('Stock transfer already confirmed');
                }

                if ($existing->status === 'rejected') {
                    throw new \RuntimeException('Rejected stock transfer cannot be confirmed');
                }

                $result = $this->stock_transfer_repository->update($id, [
                    'status' => 'confirmed',
                    'confirmed_by' => auth()->user()->id,
                    'confirmed_at' => now(),
                ]);

                if (!$result) {
                    return null;
                }

                $stockTransfer = $this->stock_transfer_repository->find($id);

                if ($stockTransfer) {
                    $stockTransfer->loadMissing([
                        'source_inventory.branches',
                        'target_inventory.branches',
                        'lines.product',
                        'lines.uom',
                    ]);

                    $this->stock_ledger_service->clearByReference(
                        referenceType: 'transfer_out',
                        referenceId: $stockTransfer->id,
                        voucherNo: $stockTransfer->reference_id
                    );

                    $this->stock_ledger_service->clearByReference(
                        referenceType: 'transfer_in',
                        referenceId: $stockTransfer->id,
                        voucherNo: $stockTransfer->reference_id
                    );

                    $rows = [];

                    foreach ($stockTransfer->lines as $line) {
                        // Get the product's stock UOM
                        $stockUomId = (int) ($line->product->stock_uom_id ?? $line->uom_id);
                        $transferUomId = (int) $line->uom_id;
                        $transferQuantity = (float) $line->quantity;
                        
                        // Convert the quantity from transfer UOM to stock UOM
                        $stockQuantity = $this->uom_conversion_service->convertToBaseUom(
                            $transferQuantity,
                            $transferUomId,
                            $stockUomId
                        );

                        // Get the current balance for this product in source inventory to calculate actual unit cost
                        $currentBalance = $this->stock_ledger_service->getRunningBalances(
                            $line->product->sku,
                            (int) $stockTransfer->source_inventory_id,
                            $line->lot_no ?? null
                        );

                        // Calculate actual unit cost from current balance
                        // If there's quantity in the balance, use the average cost
                        // Otherwise, use the product's purchase price as fallback
                        $quantityBefore = (float) $currentBalance['quantity_before'];
                        $costBefore = (float) $currentBalance['cost_before'];
                        
                        if ($quantityBefore > 0) {
                            // Average cost = total cost / total quantity
                            $unitCost = $costBefore / $quantityBefore;
                        } else {
                            // Fallback to purchase price if no balance
                            $unitCost = (float) ($line->product->purchase_price ?? 0);
                        }

                        // Calculate total cost: quantity in stock UOM * actual unit cost
                        $totalCost = $stockQuantity * $unitCost;

                        $base = [
                            'transaction_date' => $stockTransfer->transfer_date,
                            'reference_id' => $stockTransfer->id,
                            'voucher_no' => $stockTransfer->reference_id,
                            'product_id' => $line->product_id,
                            'sku' => $line->product->sku ?? '-',
                            'lot_no' => $line->lot_no ?? null,
                            'quantity' => $stockQuantity,
                            'uom_id' => $stockUomId,
                            'unit_cost' => $unitCost,
                            'total_cost' => $totalCost,
                        ];

                        $rows[] = $base + [
                            'reference_type' => 'transfer_out',
                            'inventory_id' => $stockTransfer->source_inventory_id,
                            'branch_name' => $this->stock_ledger_service->resolveBranchName($stockTransfer->source_inventory),
                            'movement_type' => 'out',
                        ];

                        $rows[] = $base + [
                            'reference_type' => 'transfer_in',
                            'inventory_id' => $stockTransfer->target_inventory_id,
                            'branch_name' => $this->stock_ledger_service->resolveBranchName($stockTransfer->target_inventory),
                            'movement_type' => 'in',
                        ];
                    }

                    $this->stock_ledger_service->addBulkStockLedger($rows);
                }

                return $stockTransfer;
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to confirm stock transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function reject(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $existing = $this->stock_transfer_repository->find($id);

                if (!$existing) {
                    return null;
                }

                if ($existing->status === 'rejected') {
                    throw new \RuntimeException('Stock transfer already rejected');
                }

                if ($existing->status === 'confirmed') {
                    throw new \RuntimeException('Confirmed stock transfer cannot be rejected');
                }

                $result = $this->stock_transfer_repository->update($id, [
                    'status' => 'rejected',
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);

                if (!$result) {
                    return null;
                }

                return $this->stock_transfer_repository->find($id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to reject stock transfer: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->stock_transfer_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find stock transfer with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    // format INW-ST-26-4-4-0001
    private function referenceIdGenerate()
    {
        $lastRecord = $this->stock_transfer_repository->getLastRecord();
        $lastId = $lastRecord ? (int) substr($lastRecord->reference_id, strrpos($lastRecord->reference_id, '-') + 1) : 0;
        $newId = $lastId + 1;
        $datePart = date('y-n-j');

        return 'INW-ST-' . $datePart . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
    }
}
