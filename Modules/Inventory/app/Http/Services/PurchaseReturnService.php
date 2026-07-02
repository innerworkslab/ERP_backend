<?php

namespace Modules\Inventory\app\Http\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\app\Http\Services\JournalService;
use Modules\Accounting\app\Models\Account;
use Modules\Inventory\app\Http\Repositories\PurchaseReturnRepository;
use Modules\Inventory\app\Models\GoodsReceiveNotes;
use Modules\Inventory\app\Models\PurchaseReturn;
use Modules\Organization\app\Models\Currency;
use Modules\Product\app\Models\Product;

class PurchaseReturnService
{
    public function __construct(
        protected PurchaseReturnRepository $repo,
        protected UOMConversionService $uomConversionService,
        protected StockLedgerService $stockLedgerService,
        protected JournalService $journalService
    ) {
    }

    public function list(int $perPage, int $page, ?string $search = null, ?string $returnNo = null, array $conditions = [])
    {
        $queryConditions = $conditions;
        unset($queryConditions['return_date_from'], $queryConditions['return_date_to']);

        return $this->repo->getListWithFilters(
            $perPage,
            $page,
            $search,
            $returnNo,
            $queryConditions,
            $conditions['return_date_from'] ?? null,
            $conditions['return_date_to'] ?? null
        );
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
                        '__returnable' => $returnable,
                    ];
                })
                ->filter(fn ($line) => $line['__returnable'] > 0)
                ->map(function ($line) {
                    unset($line['__returnable']);
                    return $line;
                })
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
            $this->postSupplierClaimJournal($purchaseReturn);

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
        $exchangeType = $attributes['exchange_type'] ?? null;

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

            if ($attributes['return_type'] === 'exchange' && $exchangeType === 'full') {
                $fullQty = (float) $grnLine->good_quantity;
                if (round($returnQty, 2) !== round($fullQty, 2)) {
                    throw new \RuntimeException('Full exchange requires each selected GRN line to be returned in full quantity.');
                }
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
                'return_quantity' => $returnQty,
                'unit_price' => (float) $grnLine->unit_price,
                'tax_amount' => $lineTax,
                'line_total' => $lineTotal,
                'reason' => $line['reason'] ?? null,
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
            'exchange_type' => $attributes['exchange_type'] ?? null,
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

    private function postStock(PurchaseReturn $purchaseReturn): void
    {
        $purchaseReturn->loadMissing(['inventory.branches', 'lines.product']);
        $rows = [];

        foreach ($purchaseReturn->lines as $line) {
            $product = $line->product ?? Product::find($line->product_id);
            $stockUomId = (int) ($product?->stock_uom_id ?? $line->uom_id);
            $stockQty = $this->uomConversionService->convertToBaseUom((float) $line->return_quantity, (int) $line->uom_id, $stockUomId);
            $unitCost = (float) $line->return_quantity > 0
                ? round((float) $line->line_total / (float) $line->return_quantity, 4)
                : (float) $line->unit_price;

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
                'unit_cost' => $unitCost,
                'total_cost' => round($stockQty * $unitCost, 2),
            ];
        }

        $this->stockLedgerService->clearByReference('purchase_return', null, $purchaseReturn->return_no);
        $this->stockLedgerService->addBulkStockLedger($rows);
    }

    private function postSupplierClaimJournal(PurchaseReturn $purchaseReturn): void
    {
        $inventoryAccount = $this->resolveInventoryAccount();
        $claimAccount = $this->resolveClaimAccount();
        $currencyId = (int) $purchaseReturn->currency_id;
        $rate = $this->currencyRate($purchaseReturn);
        $amount = (float) $purchaseReturn->total_amount;

        $this->journalService->createEntry([
            'source_type' => 'purchase_return',
            'source_id' => $purchaseReturn->id,
            'description' => 'Purchase return posting for ' . $purchaseReturn->return_no,
            'journal_datetime' => now(),
        ], [
            [
                'account_id' => $claimAccount->id,
                'type' => 'debit',
                'currency_id' => $currencyId,
                'amount' => $amount,
                'base_currency_amount' => round($amount * $rate, 8),
            ],
            [
                'account_id' => $inventoryAccount->id,
                'type' => 'credit',
                'currency_id' => $currencyId,
                'amount' => $amount,
                'base_currency_amount' => round($amount * $rate, 8),
            ],
        ]);
    }

    private function resolveInventoryAccount(): Account
    {
        $account = Account::where('code', '2-1024')->first();
        if (!$account) {
            throw new \RuntimeException('Inventory account (2-1024) is missing in COA.');
        }

        return $account;
    }

    private function resolveClaimAccount(): Account
    {
        $account = Account::where('code', '2-1055')->first();
        if (!$account) {
            throw new \RuntimeException('Claim account (2-1055) is missing in COA.');
        }

        return $account;
    }

    private function currencyRate(PurchaseReturn $purchaseReturn): float
    {
        $currency = Currency::find((int) $purchaseReturn->currency_id);

        return (float) ($currency?->exchange_rate ?: 1);
    }

    private function nextNumber(): string
    {
        $last = $this->repo->getLastRecord();
        $lastId = $last ? (int) substr($last->return_no, strrpos($last->return_no, '-') + 1) : 0;

        return 'INW-PR-' . date('y-n-j') . '-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}
