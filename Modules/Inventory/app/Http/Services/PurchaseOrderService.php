<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\app\Http\Repositories\AccountRepository;
use Modules\Accounting\app\Models\Account;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Accounting\app\Models\CashbookLedger;
use Modules\Accounting\app\Models\CashbookTransaction;
use Modules\Accounting\app\Models\JournalEntry;
use Modules\Accounting\app\Models\JournalPosting;
use Modules\Organization\app\Models\Currency;
use Modules\Inventory\app\Http\Repositories\PurchaseOrderRepository;

class PurchaseOrderService
{
    protected $purchase_order_repository;
    protected $account_repository;

    public function __construct(
        PurchaseOrderRepository $purchase_order_repository,
        AccountRepository $account_repository
    )
    {
        $this->purchase_order_repository = $purchase_order_repository;
        $this->account_repository = $account_repository;
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
                    return ['status' => 'item_not_found'];
                }

                if ($existing->status !== 'pending' && $existing->status !== 'draft') {
                    return ['status' => 'invalid_status'];
                }

                $updated = $this->purchase_order_repository->updateWithLines($id, $attributes);
                return ['status' => 'success', 'data' => $updated];
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
                return ['status' => 'item_not_found'];
            }

            if ($existing->status !== 'pending' && $existing->status !== 'draft') {
                return ['status' => 'invalid_status'];
            }

            return DB::transaction(function () use ($id) {
                $this->purchase_order_repository->delete($id);
                return ['status' => 'success'];
            });
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
                    return ['status' => 'item_not_found'];
                }

                $this->purchase_order_repository->update($id, ['status' => $status]);

                return ['status' => 'success', 'data' => $this->purchase_order_repository->find($id)];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update purchase order status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updatePaymentStatus(int $id, array $payload = [])
    {
        try {
            return DB::transaction(function () use ($id, $payload) {
                $existing = $this->purchase_order_repository->find($id);

                if (!$existing) {
                    return ['status' => 'item_not_found'];
                }

                if ($existing->payment_status === 'paid') {
                    return ['status' => 'already_paid'];
                }

                $totalAmount = (float) $existing->total_amount;
                $currencyId = (int) $existing->currency_id;
                $paidAmount = (float) ($payload['paid_amount'] ?? 0);
                $cashbookId = $payload['cashbook_id'] ?? null;

                if ($paidAmount > $totalAmount) {
                    return ['status' => 'paid_amount_exceeded'];
                }

                if ($paidAmount > 0 && !$cashbookId) {
                    return ['status' => 'cashbook_required'];
                }

                $remainingAmount = round($totalAmount - $paidAmount, 2);
                $normalizedPaymentStatus = $remainingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');

                $apParent = Account::where('code', '4-2100')->first();
                if (!$apParent) {
                    return ['status' => 'ap_parent_missing'];
                }

                $inventoryAccount = Account::where('code', '2-1024')->first();
                if (!$inventoryAccount) {
                    return ['status' => 'inventory_account_missing'];
                }

                $supplier = $existing->supplier;
                if (!$supplier) {
                    return ['status' => 'supplier_missing'];
                }

                $trxCurrency = Currency::find($currencyId);
                if (!$trxCurrency) {
                    return ['status' => 'currency_invalid'];
                }
                $exchangeRate = (float) ($trxCurrency->exchange_rate ?: 1);

                $supplierApAccount = Account::where('parent_account_id', $apParent->id)
                    ->where('name', $supplier->name)
                    ->first();

                if (!$supplierApAccount) {
                    $supplierApCode = $this->account_repository->generateAccountCode($apParent->id);

                    $supplierApAccount = Account::create([
                        'parent_account_id' => $apParent->id,
                        'code' => $supplierApCode,
                        'name' => $supplier->name,
                        'type' => 'Accounts Payable',
                        'division' => 'SOFP',
                        'description' => 'Auto generated AP account for supplier #' . $supplier->name,
                        'is_active' => true,
                    ]);
                } else {
                    if (!$supplierApAccount->is_active) {
                        $supplierApAccount->update(['is_active' => true]);
                    }
                }

                if ($paidAmount > 0) {
                    $cashbook = Cashbook::lockForUpdate()->find($cashbookId);
                    if (!$cashbook) {
                        return ['status' => 'cashbook_not_found'];
                    }

                    if ((int) $cashbook->currency_id !== $currencyId) {
                        return ['status' => 'cashbook_currency_mismatch'];
                    }

                    $lastLedger = CashbookLedger::where('cashbook_id', $cashbook->id)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();

                    $beforeBalance = $lastLedger ? (float) $lastLedger->after_balance : (float) $cashbook->current_balance;
                    $afterBalance = $beforeBalance - $paidAmount;

                    // if ($afterBalance < 0) {
                    //     throw new \RuntimeException('Insufficient cashbook balance.');
                    // }

                    $cashbookTransaction = CashbookTransaction::create([
                        'cashbook_id' => $cashbook->id,
                        'source_account_id' => $cashbook->account_id,
                        'destination_account_id' => $supplierApAccount->id,
                        'transaction_type' => 'out',
                        'category' => 'others',
                        'transaction_datetime' => now(),
                        'currency_id' => $currencyId,
                        'amount' => $paidAmount,
                        'base_currency_amount' => round($paidAmount * $exchangeRate, 8),
                        'reference_no' => 'POPAY-' . now()->format('YmdHis') . '-' . str_pad((string) $existing->id, 6, '0', STR_PAD_LEFT),
                        'remark' => 'PO Payment',
                        'description' => 'Payment for purchase order ' . $existing->po_number,
                        'status' => 'confirmed',
                        'created_by' => auth()->id(),
                    ]);

                    CashbookLedger::create([
                        'cashbook_id' => $cashbook->id,
                        'cashbook_transaction_id' => $cashbookTransaction->id,
                        'transaction_datetime' => now(),
                        'transaction_type' => 'out',
                        'amount' => $paidAmount,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'remark' => 'PO Payment',
                        'description' => 'Cashbook outflow for PO ' . $existing->po_number,
                    ]);

                    $cashbook->update([
                        'current_balance' => $afterBalance,
                        'updated_by' => auth()->id(),
                    ]);
                }

                $voucherPrefix = 'JV-' . now()->format('Ymd') . '-';
                $lastVoucher = JournalEntry::where('voucher_no', 'like', $voucherPrefix . '%')
                    ->orderByDesc('id')
                    ->value('voucher_no');
                $nextVoucherSeq = 1;
                if ($lastVoucher) {
                    $nextVoucherSeq = ((int) substr($lastVoucher, -4)) + 1;
                }

                $journalEntry = JournalEntry::create([
                    'voucher_no' => $voucherPrefix . str_pad((string) $nextVoucherSeq, 4, '0', STR_PAD_LEFT),
                    'journal_date' => now()->toDateString(),
                    'journal_datetime' => now(),
                    'source_type' => 'purchase_order',
                    'source_id' => $existing->id,
                    'description' => 'PO payment/AP posting for ' . $existing->po_number,
                ]);

                $baseTotalAmount = round($totalAmount * $exchangeRate, 8);
                $basePaidAmount = round($paidAmount * $exchangeRate, 8);
                $baseRemainingAmount = round($remainingAmount * $exchangeRate, 8);

                JournalPosting::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $inventoryAccount->id,
                    'type' => 'debit',
                    'currency_id' => $currencyId,
                    'amount' => $totalAmount,
                    'base_currency_amount' => $baseTotalAmount,
                ]);

                if ($paidAmount > 0) {
                    $cashbook = Cashbook::find($cashbookId);
                    JournalPosting::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $cashbook->account_id,
                        'type' => 'credit',
                        'currency_id' => $currencyId,
                        'amount' => $paidAmount,
                        'base_currency_amount' => $basePaidAmount,
                    ]);
                }

                if ($remainingAmount > 0) {
                    JournalPosting::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $supplierApAccount->id,
                        'type' => 'credit',
                        'currency_id' => $currencyId,
                        'amount' => $remainingAmount,
                        'base_currency_amount' => $baseRemainingAmount,
                    ]);
                }

                $this->purchase_order_repository->update($id, [
                    'paid_amount' => round($paidAmount, 2),
                    'payment_status' => $normalizedPaymentStatus,
                ]);

                return ['status' => 'success', 'data' => $this->purchase_order_repository->find($id)];
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
                    return ['status' => 'item_not_found'];
                }

                $this->purchase_order_repository->update($id, ['delivery_status' => $deliveryStatus]);

                return ['status' => 'success', 'data' => $this->purchase_order_repository->find($id)];
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
            $expensesAmount = (float) ($attributes['expenses_amount'] ?? 0);

            $grossAmount = $quantity * $unitPrice;
            $lineTotal = $grossAmount - $discountAmount + $taxAmount - $expensesAmount;

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'expenses_amount' => $expensesAmount,
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
