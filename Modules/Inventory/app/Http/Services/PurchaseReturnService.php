<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\app\Models\Account;
use Modules\Accounting\app\Models\JournalEntry;
use Modules\Accounting\app\Models\JournalPosting;
use Modules\Inventory\app\Http\Repositories\GoodsReceiveNoteRepository;
use Modules\Inventory\app\Http\Repositories\PurchaseReturnRepository;
use Modules\Inventory\app\Models\GoodsReceiveNotes;
use Modules\Product\app\Models\Product;

class PurchaseReturnService
{
    public function __construct(
        protected PurchaseReturnRepository $repo,
        protected UOMConversionService $uomConversionService,
        protected StockLedgerService $stockLedgerService,
        protected GoodsReceiveNoteRepository $goodsReceiveNoteRepository
    ) {
    }

    public function list(int $perPage, int $page, array $searches = [], array $conditions = [])
    {
        $queryConditions = $conditions;
        unset($queryConditions['return_date_from'], $queryConditions['return_date_to']);

        $result = $this->repo->getDataWithPagination(
            $perPage,
            $page,
            'created_at',
            $searches,
            $queryConditions,
            [],
            ['goodsReceiveNote', 'purchaseOrder', 'supplier', 'branch', 'inventory', 'currency']
        );

        if (!empty($conditions['return_date_from']) || !empty($conditions['return_date_to'])) {
            $result['data'] = $result['data']->filter(function ($item) use ($conditions) {
                $from = $conditions['return_date_from'] ?? null;
                $to = $conditions['return_date_to'] ?? null;

                if ($from && $item->return_date < $from) {
                    return false;
                }

                if ($to && $item->return_date > $to) {
                    return false;
                }

                return true;
            })->values();

            $result['meta']['total'] = $result['data']->count();
            $result['meta']['total_pages'] = (int) ceil($result['meta']['total'] / max((int) $perPage, 1));
        }

        return $result;
    }

    public function find(int $id)
    {
        return $this->repo->find($id);
    }

    public function getReturnableLines(int $grnId): array
    {
        $grn = GoodsReceiveNotes::with([
            'supplier',
            'purchaseOrder',
            'branch',
            'inventory',
            'currency',
            'lines.product',
            'lines.uom',
            'lines.purchaseOrderLine.tax',
        ])->find($grnId);

        if (!$grn) {
            throw new \RuntimeException('Goods receive note not found.');
        }

        if ($grn->status !== 'approved') {
            throw new \RuntimeException('Only approved GRN can be returned.');
        }

        return [
            'goods_receive_note_id' => $grn->id,
            'grn_no' => $grn->grn_no,
            'purchase_order_id' => $grn->purchase_order_id,
            'purchase_order_no' => $grn->purchaseOrder?->po_number,
            'supplier_id' => $grn->supplier_id,
            'supplier_name' => $grn->supplier?->name,
            'branch_id' => $grn->branch_id,
            'branch_name' => $grn->branch?->name,
            'inventory_id' => $grn->inventory_id,
            'inventory_name' => $grn->inventory?->name,
            'currency_id' => $grn->currency_id,
            'currency_code' => $grn->currency?->code,
            'lines' => $grn->lines
                ->map(function ($line) {
                    $alreadyReturned = $this->approvedReturnedQuantityForLine((int) $line->id);
                    $returnable = max((float) $line->good_quantity - $alreadyReturned, 0);
                    $poLine = $line->purchaseOrderLine;

                    return [
                        'goods_receive_note_line_id' => $line->id,
                        'purchase_order_line_id' => $line->purchase_order_line_id,
                        'product_id' => $line->product_id,
                        'product_name' => $line->product?->name,
                        'sku' => $line->product?->sku,
                        'uom_id' => $line->uom_id,
                        'uom_name' => $line->uom?->name,
                        'unit_price' => (float) $line->unit_price,
                        'final_unit_cost' => (float) $line->final_unit_cost,
                        'tax_id' => $poLine?->tax_id,
                        'tax_amount' => $this->proportionalTaxAmount($poLine?->quantity, $poLine?->tax_amount, $line->good_quantity),
                        'grn_good_quantity' => (float) $line->good_quantity,
                        'already_returned_quantity' => $alreadyReturned,
                        'returnable_quantity' => $returnable,
                    ];
                })
                ->filter(fn ($line) => $line['returnable_quantity'] > 0)
                ->values()
                ->all(),
        ];
    }

    public function create(array $attributes)
    {
        return DB::transaction(function () use ($attributes) {
            $normalized = $this->normalize($attributes);
            $normalized['return_no'] = $this->nextNumber();
            $normalized['created_by'] = auth()->id();

            return $this->repo->createWithLines($normalized);
        });
    }

    public function update(int $id, array $attributes)
    {
        return DB::transaction(function () use ($id, $attributes) {
            $existing = $this->repo->find($id);
            if (!$existing) {
                return ['status' => 'not_found'];
            }

            if ($existing->status !== 'pending') {
                return ['status' => 'invalid_status'];
            }

            $normalized = $this->normalize($attributes, $existing->id);
            $normalized['return_no'] = $existing->return_no;
            $normalized['created_by'] = $existing->created_by;
            $normalized['exchange_goods_receive_note_id'] = $existing->exchange_goods_receive_note_id;

            $updated = $this->repo->updateWithLines($id, $normalized);

            return ['status' => 'success', 'data' => $updated];
        });
    }

    public function approve(int $id)
    {
        return DB::transaction(function () use ($id) {
            $purchaseReturn = $this->repo->find($id);
            if (!$purchaseReturn) {
                return ['status' => 'not_found'];
            }

            if ($purchaseReturn->status === 'approved') {
                return ['status' => 'already_approved'];
            }

            if ($purchaseReturn->status === 'rejected') {
                return ['status' => 'already_rejected'];
            }

            $this->repo->update($id, [
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $purchaseReturn = $this->repo->find($id);
            $this->postStock($purchaseReturn);
            $this->postAccounting($purchaseReturn);

            if ($purchaseReturn->return_type === 'exchange') {
                $exchangeGrn = $this->createExchangeGrnDraft($purchaseReturn);
                $this->repo->update($purchaseReturn->id, [
                    'exchange_goods_receive_note_id' => $exchangeGrn->id,
                ]);
            }

            return ['status' => 'success', 'data' => $this->repo->find($id)];
        });
    }

    public function reject(int $id)
    {
        $purchaseReturn = $this->repo->find($id);
        if (!$purchaseReturn) {
            return ['status' => 'not_found'];
        }

        if ($purchaseReturn->status !== 'pending') {
            return ['status' => 'invalid_status'];
        }

        $this->repo->update($id, ['status' => 'rejected']);

        return ['status' => 'success', 'data' => $this->repo->find($id)];
    }

    private function normalize(array $attributes, ?int $ignorePurchaseReturnId = null): array
    {
        $grn = GoodsReceiveNotes::with([
            'lines.purchaseOrderLine',
            'purchaseOrder',
        ])->find($attributes['goods_receive_note_id']);

        if (!$grn) {
            throw new \RuntimeException('Goods receive note not found.');
        }

        if ($grn->status !== 'approved') {
            throw new \RuntimeException('Only approved GRN can be returned.');
        }

        $grnLines = $grn->lines->keyBy('id');
        $normalizedLines = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($attributes['lines'] as $line) {
            $grnLine = $grnLines->get((int) $line['goods_receive_note_line_id']);
            if (!$grnLine) {
                throw new \RuntimeException('Selected GRN line does not belong to the chosen GRN.');
            }

            $alreadyReturned = $this->approvedReturnedQuantityForLine($grnLine->id, $ignorePurchaseReturnId);
            $returnable = max((float) $grnLine->good_quantity - $alreadyReturned, 0);
            $returnQty = (float) $line['return_quantity'];

            if ($returnQty > $returnable) {
                throw new \RuntimeException('Return quantity cannot exceed returnable quantity.');
            }

            $poLine = $grnLine->purchaseOrderLine;
            $lineTax = $this->proportionalTaxAmount($poLine?->quantity, $poLine?->tax_amount, $returnQty);
            $lineSubtotal = round($returnQty * (float) $grnLine->final_unit_cost, 2);
            $lineTotal = round($lineSubtotal + $lineTax, 2);

            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;

            $normalizedLines[] = [
                'goods_receive_note_line_id' => $grnLine->id,
                'purchase_order_line_id' => $grnLine->purchase_order_line_id,
                'product_id' => $grnLine->product_id,
                'uom_id' => $grnLine->uom_id,
                'tax_id' => $poLine?->tax_id,
                'grn_good_quantity' => (float) $grnLine->good_quantity,
                'already_returned_quantity' => $alreadyReturned,
                'returnable_quantity' => $returnable,
                'return_quantity' => $returnQty,
                'unit_price' => (float) $grnLine->unit_price,
                'final_unit_cost' => (float) $grnLine->final_unit_cost,
                'tax_amount' => $lineTax,
                'line_total' => $lineTotal,
                'reason' => $line['reason'] ?? null,
                'remarks' => $line['remarks'] ?? null,
            ];
        }

        if (count($normalizedLines) === 0) {
            throw new \RuntimeException('At least one return line is required.');
        }

        return [
            'return_no' => $attributes['return_no'] ?? null,
            'goods_receive_note_id' => $grn->id,
            'purchase_order_id' => $grn->purchase_order_id,
            'supplier_id' => $grn->supplier_id,
            'branch_id' => $grn->branch_id,
            'inventory_id' => $grn->inventory_id,
            'currency_id' => $grn->currency_id,
            'return_date' => $attributes['return_date'],
            'return_type' => $attributes['return_type'],
            'subtotal_amount' => round($subtotal, 2),
            'tax_amount' => round($taxTotal, 2),
            'total_amount' => round($subtotal + $taxTotal, 2),
            'remarks' => $attributes['remarks'] ?? null,
            'status' => 'pending',
            'lines' => $normalizedLines,
        ];
    }

    private function approvedReturnedQuantityForLine(int $grnLineId, ?int $ignorePurchaseReturnId = null): float
    {
        return (float) DB::table('goods_return_lines as grl')
            ->join('purchase_returns as pr', 'pr.id', '=', 'grl.purchase_return_id')
            ->where('grl.goods_receive_note_line_id', $grnLineId)
            ->where('pr.status', 'approved')
            ->when($ignorePurchaseReturnId, fn ($query) => $query->where('pr.id', '!=', $ignorePurchaseReturnId))
            ->sum('grl.return_quantity');
    }

    private function proportionalTaxAmount($poQuantity, $poTaxAmount, $returnQuantity): float
    {
        $poQty = (float) ($poQuantity ?? 0);
        $tax = (float) ($poTaxAmount ?? 0);
        $returnQty = (float) ($returnQuantity ?? 0);

        if ($poQty <= 0 || $tax <= 0 || $returnQty <= 0) {
            return 0.0;
        }

        return round(($tax / $poQty) * $returnQty, 2);
    }

    private function postStock($purchaseReturn): void
    {
        $purchaseReturn->loadMissing(['inventory.branches', 'lines.product']);
        $rows = [];

        foreach ($purchaseReturn->lines as $line) {
            $product = $line->product ?? Product::find($line->product_id);
            $stockUomId = (int) ($product?->stock_uom_id ?? $line->uom_id);
            $stockQty = $this->uomConversionService->convertToBaseUom((float) $line->return_quantity, (int) $line->uom_id, $stockUomId);

            $rows[] = [
                'transaction_date' => $purchaseReturn->return_date,
                'reference_type' => 'purchase_return',
                'reference_id' => null,
                'voucher_no' => $purchaseReturn->return_no,
                'inventory_id' => $purchaseReturn->inventory_id,
                'branch_name' => $this->stockLedgerService->resolveBranchName($purchaseReturn->inventory),
                'movement_type' => 'out',
                'product_id' => $line->product_id,
                'sku' => $product?->sku ?? '-',
                'lot_no' => null,
                'quantity' => $stockQty,
                'uom_id' => $stockUomId,
                'unit_cost' => (float) $line->final_unit_cost,
                'total_cost' => round($stockQty * (float) $line->final_unit_cost, 2),
            ];
        }

        $this->stockLedgerService->clearByReference('purchase_return', null, $purchaseReturn->return_no);
        $this->stockLedgerService->addBulkStockLedger($rows);
    }

    private function postAccounting($purchaseReturn): void
    {
        $inventoryAccount = Account::where('code', '2-1024')->first();
        $supplierReceivable = Account::where('code', '2-1055')->first();

        if (!$inventoryAccount || !$supplierReceivable) {
            throw new Exception('Required accounts are missing.');
        }

        $entry = JournalEntry::create([
            'journal_datetime' => now(),
            'source_type' => 'purchase_return',
            'source_id' => $purchaseReturn->id,
            'description' => 'Purchase return posting for ' . $purchaseReturn->return_no,
        ]);

        $currencyId = (int) $purchaseReturn->currency_id;
        $rate = (float) ($purchaseReturn->currency?->exchange_rate ?? 1);
        $amount = (float) $purchaseReturn->total_amount;

        JournalPosting::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $supplierReceivable->id,
            'type' => 'debit',
            'currency_id' => $currencyId,
            'amount' => $amount,
            'base_currency_amount' => round($amount * $rate, 8),
        ]);

        JournalPosting::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $inventoryAccount->id,
            'type' => 'credit',
            'currency_id' => $currencyId,
            'amount' => $amount,
            'base_currency_amount' => round($amount * $rate, 8),
        ]);
    }

    private function createExchangeGrnDraft($purchaseReturn)
    {
        $purchaseReturn->loadMissing(['purchaseOrder', 'lines.goodsReceiveNoteLine']);

        $exchangeGrn = $this->goodsReceiveNoteRepository->createWithRelations([
            'grn_no' => $this->nextExchangeGrnNumber(),
            'purchase_order_id' => $purchaseReturn->purchase_order_id,
            'supplier_id' => $purchaseReturn->supplier_id,
            'branch_id' => $purchaseReturn->branch_id,
            'inventory_id' => $purchaseReturn->inventory_id,
            'currency_id' => $purchaseReturn->currency_id,
            'grn_date' => $purchaseReturn->return_date,
            'fee_allocation_method' => 'by_line_value',
            'tax_allocation_method' => 'by_weight',
            'remarks' => 'Purchase Return - Exchange',
            'subtotal_amount' => $purchaseReturn->subtotal_amount,
            'discount_amount' => 0,
            'cargo_tax_amount' => 0,
            'charge_total_amount' => 0,
            'total_amount' => $purchaseReturn->subtotal_amount,
            'status' => 'pending',
            'created_by' => auth()->id(),
            'lines' => collect($purchaseReturn->lines)->map(function ($line) {
                return [
                    'purchase_order_line_id' => $line->purchase_order_line_id,
                    'product_id' => $line->product_id,
                    'uom_id' => $line->uom_id,
                    'ordered_quantity' => (float) ($line->goodsReceiveNoteLine?->ordered_quantity ?? $line->return_quantity),
                    'previously_received_quantity' => 0,
                    'remaining_quantity' => 0,
                    'received_quantity' => (float) $line->return_quantity,
                    'good_quantity' => (float) $line->return_quantity,
                    'short_quantity' => 0,
                    'discrepancy_reason' => 'none',
                    'defect_responsibility' => null,
                    'unit_price' => (float) $line->unit_price,
                    'line_weight' => 0,
                    'allocated_charge_amount' => 0,
                    'allocated_tax_amount' => 0,
                    'final_unit_cost' => (float) $line->final_unit_cost,
                    'line_total' => round((float) $line->return_quantity * (float) $line->unit_price, 2),
                    'remarks' => 'Purchase Return - Exchange',
                ];
            })->all(),
            'charges' => [],
        ]);

        return $exchangeGrn;
    }

    private function nextNumber(): string
    {
        $last = $this->repo->getLastRecord();
        $lastId = $last ? (int) substr($last->return_no, strrpos($last->return_no, '-') + 1) : 0;

        return 'INW-PR-' . date('y-n-j') . '-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextExchangeGrnNumber(): string
    {
        $last = $this->goodsReceiveNoteRepository->getLastRecord();
        $lastId = $last ? (int) substr($last->grn_no, strrpos($last->grn_no, '-') + 1) : 0;

        return 'INW-GRN-' . date('y-n-j') . '-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}
