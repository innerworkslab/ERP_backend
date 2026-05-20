<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\app\Http\Repositories\PurchaseOrderRepository;

class PurchaseOrderService
{
    protected $purchase_order_repository;

    public function __construct(PurchaseOrderRepository $purchase_order_repository)
    {
        $this->purchase_order_repository = $purchase_order_repository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        array $searches = null,
        array $conditions = [],
        ?array $whereHas = null
    ) {
        try {
            $queryConditions = $conditions;
            unset($queryConditions['po_date_from'], $queryConditions['po_date_to']);

            $result = $this->purchase_order_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                searches: $searches,
                with: ['supplier', 'branch', 'inventory', 'currency', 'createdBy'],
                conditions: $queryConditions,
                whereHas: $whereHas
            );

            if (!empty($conditions['po_date_from']) || !empty($conditions['po_date_to'])) {
                $result['data'] = $result['data']->filter(function ($item) use ($conditions) {
                    $from = $conditions['po_date_from'] ?? null;
                    $to = $conditions['po_date_to'] ?? null;

                    if ($from && $item->po_date < $from) {
                        return false;
                    }

                    if ($to && $item->po_date > $to) {
                        return false;
                    }

                    return true;
                })->values();
            }

            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch purchase orders with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->purchase_order_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch purchase order: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return DB::transaction(function () use ($attributes) {
                $attributes['po_number'] = $this->poNumberGenerate();
                $attributes['created_by'] = auth()->id();

                return $this->purchase_order_repository->create($attributes);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create purchase order: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $existing = $this->purchase_order_repository->find($id);

                if (!$existing) {
                    return null;
                }

                if ($existing->status !== 'pending' && $existing->status !== 'draft') {
                    throw new \RuntimeException('Only pending or draft purchase order can be updated');
                }

                return $this->purchase_order_repository->updateWithLines($id, $attributes);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update purchase order: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $existing = $this->purchase_order_repository->find($id);

            if (!$existing) {
                return false;
            }

            if ($existing->status !== 'pending' && $existing->status !== 'draft') {
                throw new \RuntimeException('Only pending or draft purchase order can be deleted');
            }

            return DB::transaction(fn() => $this->purchase_order_repository->delete($id));
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete purchase order: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status)
    {
        try {
            return DB::transaction(function () use ($id, $status) {
                $existing = $this->purchase_order_repository->find($id);

                if (!$existing) {
                    return null;
                }

                $this->purchase_order_repository->update($id, ['status' => $status]);

                return $this->purchase_order_repository->find($id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update purchase order status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updatePaymentStatus(int $id, string $paymentStatus)
    {
        try {
            return DB::transaction(function () use ($id, $paymentStatus) {
                $existing = $this->purchase_order_repository->find($id);

                if (!$existing) {
                    return null;
                }

                $this->purchase_order_repository->update($id, ['payment_status' => $paymentStatus]);

                return $this->purchase_order_repository->find($id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update purchase order payment status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateDeliveryStatus(int $id, string $deliveryStatus)
    {
        try {
            return DB::transaction(function () use ($id, $deliveryStatus) {
                $existing = $this->purchase_order_repository->find($id);

                if (!$existing) {
                    return null;
                }

                $this->purchase_order_repository->update($id, ['delivery_status' => $deliveryStatus]);

                return $this->purchase_order_repository->find($id);
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update purchase order delivery status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            return $this->purchase_order_repository->whereFirst($column, $value);
        } catch (Exception $e) {
            logger()->error('Error : Failed to find purchase order with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateLineTotal(array $attributes): array
    {
        try {
            $quantity = (float) ($attributes['quantity'] ?? 0);
            $unitPrice = (float) ($attributes['unit_price'] ?? 0);
            $discountAmount = (float) ($attributes['discount_amount'] ?? 0);
            $taxAmount = (float) ($attributes['tax_amount'] ?? 0);

            $grossAmount = $quantity * $unitPrice;
            $lineTotal = $grossAmount - $discountAmount + $taxAmount;

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'gross_amount' => round($grossAmount, 2),
                'line_total' => round($lineTotal, 2),
            ];
        } catch (Exception $e) {
            logger()->error('Error : Failed to calculate purchase order line total: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateTotalAmount(array $attributes): array
    {
        try {
            $lines = $attributes['lines'] ?? [];
            $orderDiscountAmount = (float) ($attributes['discount_amount'] ?? 0);
            $orderTaxAmount = (float) ($attributes['tax_amount'] ?? 0);

            $lineBreakdown = [];
            $subtotalAmount = 0.0;

            foreach ($lines as $index => $line) {
                $calculated = $this->calculateLineTotal($line);
                $subtotalAmount += (float) $calculated['line_total'];

                $lineBreakdown[] = [
                    'line_no' => $index + 1,
                    'gross_amount' => $calculated['gross_amount'],
                    'line_total' => $calculated['line_total'],
                ];
            }

            $totalAmount = $subtotalAmount - $orderDiscountAmount + $orderTaxAmount;

            return [
                'lines' => $lineBreakdown,
                'subtotal_amount' => round($subtotalAmount, 2),
                'discount_amount' => $orderDiscountAmount,
                'tax_amount' => $orderTaxAmount,
                'total_amount' => round($totalAmount, 2),
            ];
        } catch (Exception $e) {
            logger()->error('Error : Failed to calculate purchase order total amount: ' . $e->getMessage());
            throw $e;
        }
    }

    // format INW-PO-26-5-20-0001
    private function poNumberGenerate()
    {
        $lastRecord = $this->purchase_order_repository->getLastRecord();
        $lastId = $lastRecord ? (int) substr($lastRecord->po_number, strrpos($lastRecord->po_number, '-') + 1) : 0;
        $newId = $lastId + 1;
        $datePart = date('y-n-j');

        return 'INW-PO-' . $datePart . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
    }
}
