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
use Modules\Inventory\app\Http\Repositories\GoodsReceiveNoteRepository;
use Modules\Inventory\app\Models\PurchaseOrderLine;
use Modules\Inventory\app\Models\PurchaseOrder;
use Modules\Organization\app\Models\Currency;

class GoodsReceiveNoteService
{
    public function __construct(
        protected GoodsReceiveNoteRepository $repo,
        protected UOMConversionService $uomConversionService,
        protected StockLedgerService $stockLedgerService,
        protected AccountRepository $accountRepository
    ) {
    }

    public function list(int $perPage, int $page, array $searches = [], array $conditions = [])
    {
        $queryConditions = $conditions;
        unset($queryConditions['grn_date_from'], $queryConditions['grn_date_to']);

        $result = $this->repo->getDataWithPagination($perPage, $page, 'created_at', $searches, $queryConditions, [], [
            'purchaseOrder', 'supplier', 'branch', 'inventory', 'currency',
        ]);

        if (!empty($conditions['grn_date_from']) || !empty($conditions['grn_date_to'])) {
            $result['data'] = $result['data']->filter(function ($item) use ($conditions) {
                $from = $conditions['grn_date_from'] ?? null;
                $to = $conditions['grn_date_to'] ?? null;
                if ($from && $item->grn_date < $from) {
                    return false;
                }
                if ($to && $item->grn_date > $to) {
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

    public function calculate(array $attributes): array
    {
        return $this->normalize($attributes);
    }

    public function create(array $attributes)
    {
        return DB::transaction(function () use ($attributes) {
            $normalized = $this->normalize($attributes);
            $normalized['grn_no'] = $this->nextNumber();
            $normalized['created_by'] = auth()->id();

            return $this->repo->createWithRelations($normalized);
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
            $normalized['grn_no'] = $existing->grn_no;
            $normalized['created_by'] = $existing->created_by;
            $updated = $this->repo->updateWithRelations($id, $normalized);

            return ['status' => 'success', 'data' => $updated];
        });
    }

    public function delete(int $id)
    {
        $existing = $this->repo->find($id);
        if (!$existing) {
            return ['status' => 'not_found'];
        }
        if ($existing->status !== 'pending') {
            return ['status' => 'invalid_status'];
        }

        $this->repo->delete($id);
        return ['status' => 'success'];
    }

    public function approve(int $id, array $payload = [])
    {
        return DB::transaction(function () use ($id, $payload) {
            $grn = $this->repo->find($id);
            if (!$grn) {
                return ['status' => 'not_found'];
            }
            if ($grn->status === 'approved') {
                return ['status' => 'already_approved'];
            }
            if ($grn->status === 'rejected') {
                return ['status' => 'already_rejected'];
            }

            $this->repo->update($id, [
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $grn = $this->repo->find($id);
            $this->postStock($grn);
            $this->postAccounting($grn, $payload);
            $this->updatePoDeliveryStatus((int) $grn->purchase_order_id);

            return ['status' => 'success', 'data' => $grn];
        });
    }

    public function reject(int $id)
    {
        $grn = $this->repo->find($id);
        if (!$grn) {
            return ['status' => 'not_found'];
        }
        if ($grn->status !== 'pending') {
            return ['status' => 'invalid_status'];
        }

        $this->repo->update($id, ['status' => 'rejected']);
        return ['status' => 'success', 'data' => $this->repo->find($id)];
    }

    public function purchaseOrderTemplate(int $purchaseOrderId): array
    {
        $po = PurchaseOrder::with([
            'supplier',
            'branch',
            'inventory',
            'currency',
            'lines.product',
            'lines.uom',
        ])->find($purchaseOrderId);

        if (!$po) {
            throw new \RuntimeException('Purchase order not found.');
        }

        $receivedMap = DB::table('goods_receive_notes_lines as gl')
            ->join('goods_receive_notes as g', 'g.id', '=', 'gl.goods_receive_note_id')
            ->where('g.purchase_order_id', $po->id)
            ->where('g.status', 'approved')
            ->select('gl.purchase_order_line_id', DB::raw('SUM(gl.good_quantity) as received_quantity'))
            ->groupBy('gl.purchase_order_line_id')
            ->pluck('received_quantity', 'purchase_order_line_id');

        return [
            'purchase_order_id' => $po->id,
            'po_no' => $po->po_number,
            'po_date' => $po->po_date,
            'supplier_id' => $po->supplier_id,
            'supplier_name' => $po->supplier?->name,
            'branch_id' => $po->branch_id,
            'branch_name' => $po->branch?->name,
            'inventory_id' => $po->inventory_id,
            'inventory_name' => $po->inventory?->name,
            'currency_id' => $po->currency_id,
            'currency_code' => $po->currency?->code,
            'currency_rate' => (float) ($po->currency?->exchange_rate ?? 1),
            'delivery_status' => $po->delivery_status,
            'lines' => $po->lines->map(function ($line) use ($receivedMap) {
                $previouslyReceived = (float) ($receivedMap[$line->id] ?? 0);
                $remaining = max((float) $line->quantity - $previouslyReceived, 0);

                return [
                    'purchase_order_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product?->name,
                    'sku' => $line->product?->sku,
                    'uom_id' => $line->uom_id,
                    'uom_name' => $line->uom?->name,
                    'ordered_quantity' => (float) $line->quantity,
                    'previously_received_quantity' => $previouslyReceived,
                    'remaining_quantity' => $remaining,
                    'unit_price' => (float) $line->unit_price,
                ];
            })->values(),
        ];
    }

    private function normalize(array $attributes, ?int $ignoreGrnId = null): array
    {
        $po = PurchaseOrder::with([
            'supplier',
            'branch',
            'inventory',
            'currency',
            'lines.product',
            'lines.uom',
        ])->find($attributes['purchase_order_id']);
        if (!$po) {
            throw new \RuntimeException('Purchase order not found.');
        }

        $lines = $this->normalizeLinesFromPo($po, $attributes['lines'] ?? [], $ignoreGrnId);
        $charges = $attributes['charges'] ?? [];
        $poCurrency = $po->currency;
        $poRate = (float) ($poCurrency->exchange_rate ?? 1);

        $chargeTotal = 0.0;
        $normalizedCharges = [];
        foreach ($charges as $charge) {
            $ccy = Currency::find($charge['currency_id']);
            $rate = (float) ($ccy->exchange_rate ?? 1);
            $baseAmount = round(((float) $charge['amount']) * $rate, 2);
            $amountInPoCcy = round($baseAmount / max($poRate, 0.0000001), 2);
            $chargeTotal += $amountInPoCcy;
            $normalizedCharges[] = [
                'charge_type' => $charge['charge_type'],
                'currency_id' => $charge['currency_id'],
                'amount' => $charge['amount'],
                'base_amount' => $baseAmount,
                'description' => $charge['description'] ?? null,
            ];
        }

        $taxAmount = (float) ($attributes['cargo_tax_amount'] ?? 0);
        $discount = (float) ($attributes['discount_amount'] ?? 0);
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += ((float) $line['good_quantity']) * ((float) $line['unit_price']);
        }

        $lineAmounts = $this->allocateAmounts(
            lines: $lines,
            chargeTotal: $chargeTotal,
            taxAmount: $taxAmount,
            feeMethod: $attributes['fee_allocation_method'] ?? 'by_line_value',
            taxMethod: $attributes['tax_allocation_method'] ?? 'by_weight'
        );

        $normalizedLines = [];
        foreach ($lines as $index => $line) {
            $received = (float) $line['received_quantity'];
            $good = (float) $line['good_quantity'];
            $short = max($received - $good, 0);
            $allocatedCharge = $lineAmounts[$index]['charge'];
            $allocatedTax = $lineAmounts[$index]['tax'];
            $lineBase = $good * (float) $line['unit_price'];
            $lineTotal = $lineBase + $allocatedCharge + $allocatedTax;
            $finalUnitCost = $good > 0 ? $lineTotal / $good : 0;

            $normalizedLines[] = [
                'purchase_order_line_id' => $line['purchase_order_line_id'],
                'product_id' => $line['product_id'],
                'product_name' => $line['product_name'] ?? null,
                'sku' => $line['sku'] ?? null,
                'uom_id' => $line['uom_id'],
                'uom_name' => $line['uom_name'] ?? null,
                'ordered_quantity' => $line['ordered_quantity'],
                'previously_received_quantity' => $line['previously_received_quantity'],
                'remaining_quantity' => max((float) $line['ordered_quantity'] - (float) $line['previously_received_quantity'] - $received, 0),
                'received_quantity' => $received,
                'good_quantity' => $good,
                'short_quantity' => $short,
                'discrepancy_reason' => $short > 0 ? ($line['discrepancy_reason'] ?? 'defect') : 'none',
                'defect_responsibility' => $line['defect_responsibility'] ?? null,
                'unit_price' => $line['unit_price'],
                'line_weight' => $line['line_weight'] ?? 0,
                'allocated_charge_amount' => $allocatedCharge,
                'allocated_tax_amount' => ($attributes['tax_allocation_method'] ?? 'by_weight') === 'by_products'
                    ? (float) ($line['manual_tax_amount'] ?? 0)
                    : $allocatedTax,
                'final_unit_cost' => round($finalUnitCost, 4),
                'line_total' => round($lineTotal, 2),
                'remarks' => $line['remarks'] ?? null,
            ];
        }

        $this->assertTaxByProducts($attributes, $normalizedLines);

        $total = round($subtotal + $chargeTotal + $taxAmount - $discount, 2);

        return array_merge($attributes, [
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'branch_id' => $po->branch_id,
            'inventory_id' => $po->inventory_id,
            'currency_id' => $po->currency_id,
            'purchase_order_no' => $po->po_number,
            'supplier_name' => $po->supplier?->name,
            'branch_name' => $po->branch?->name,
            'inventory_name' => $po->inventory?->name,
            'currency_code' => $poCurrency?->code,
            'lines' => $normalizedLines,
            'charges' => $normalizedCharges,
            'subtotal_amount' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'cargo_tax_amount' => round($taxAmount, 2),
            'charge_total_amount' => round($chargeTotal, 2),
            'total_amount' => $total,
        ]);
    }

    private function normalizeLinesFromPo(PurchaseOrder $po, array $inputLines, ?int $ignoreGrnId = null): array
    {
        $poLines = PurchaseOrderLine::where('purchase_order_id', $po->id)->get()->keyBy('id');
        $normalized = [];
        $inputLines = collect($inputLines);
        $inputLineMap = $inputLines->mapWithKeys(function ($line) {
            return [(int) $line['purchase_order_line_id'] => $line];
        });

        if ($inputLineMap->count() !== $inputLines->count()) {
            throw new \RuntimeException('Duplicate purchase order line detected in GRN payload.');
        }

        $unexpectedLineIds = $inputLineMap->keys()->diff($poLines->keys());
        if ($unexpectedLineIds->isNotEmpty()) {
            throw new \RuntimeException('GRN line does not belong to purchase order.');
        }

        foreach ($poLines as $poLineId => $poLine) {
            $line = $inputLineMap->get((int) $poLineId);
            if (!$line) {
                throw new \RuntimeException('GRN line is missing for purchase order line ' . $poLineId . '.');
            }

            $prevReceived = (float) DB::table('goods_receive_notes_lines as gl')
                ->join('goods_receive_notes as g', 'g.id', '=', 'gl.goods_receive_note_id')
                ->where('g.purchase_order_id', $po->id)
                ->where('g.status', 'approved')
                ->where('gl.purchase_order_line_id', $poLineId)
                ->when($ignoreGrnId, fn ($q) => $q->where('g.id', '!=', $ignoreGrnId))
                ->sum('gl.good_quantity');

            $ordered = (float) $poLine->quantity;
            $remaining = max($ordered - $prevReceived, 0);
            $received = (float) $line['received_quantity'];
            $good = (float) ($line['good_quantity'] ?? 0);

            if ($good > $received) {
                throw new \RuntimeException('Good quantity cannot exceed received quantity.');
            }

            if ($received > $remaining) {
                throw new \RuntimeException('Received quantity cannot exceed remaining PO quantity.');
            }

            $short = max($received - $good, 0);
            if ($short > 0 && ($line['discrepancy_reason'] ?? null) === 'defect' && empty($line['defect_responsibility'])) {
                throw new \RuntimeException('Defect responsibility is required for defect discrepancy.');
            }

            $normalized[] = array_merge($line, [
                'product_id' => (int) $poLine->product_id,
                'product_name' => $poLine->product?->name,
                'sku' => $poLine->product?->sku,
                'uom_id' => (int) $poLine->uom_id,
                'uom_name' => $poLine->uom?->name,
                'ordered_quantity' => $ordered,
                'unit_price' => (float) $poLine->unit_price,
                'previously_received_quantity' => $prevReceived,
                'remaining_quantity' => $remaining,
            ]);
        }

        return $normalized;
    }

    private function assertTaxByProducts(array $attributes, array $lines): void
    {
        if (($attributes['tax_allocation_method'] ?? 'by_weight') !== 'by_products') {
            return;
        }

        $taxAmount = round((float) ($attributes['cargo_tax_amount'] ?? 0), 2);
        $lineTaxTotal = 0.0;
        foreach ($lines as $line) {
            $lineTaxTotal += (float) ($line['allocated_tax_amount'] ?? 0);
        }
        if (round($lineTaxTotal, 2) !== $taxAmount) {
            throw new \RuntimeException('Tax by products must equal total cargo tax amount.');
        }
    }

    private function allocateAmounts(array $lines, float $chargeTotal, float $taxAmount, string $feeMethod, string $taxMethod): array
    {
        $chargeWeights = $this->weights($lines, $feeMethod);
        $taxWeights = $taxMethod === 'by_products'
            ? []
            : $this->weights($lines, 'by_weight');

        $result = [];
        foreach ($lines as $index => $line) {
            $resolvedTax = $taxMethod === 'by_products'
                ? (float) ($line['manual_tax_amount'] ?? 0)
                : round($taxAmount * ($taxWeights[$index] ?? 0), 2);
            $result[$index] = [
                'charge' => round($chargeTotal * ($chargeWeights[$index] ?? 0), 2),
                'tax' => $resolvedTax,
            ];
        }

        return $result;
    }

    private function weights(array $lines, string $method): array
    {
        $metrics = [];
        foreach ($lines as $line) {
            $goodQty = (float) $line['good_quantity'];
            $metrics[] = match ($method) {
                'by_weight' => $goodQty * (float) ($line['line_weight'] ?? 0),
                'equal_qty' => $goodQty,
                'equal_line' => 1,
                default => $goodQty * (float) $line['unit_price'],
            };
        }

        $sum = array_sum($metrics);
        if ($sum <= 0) {
            $count = max(count($lines), 1);
            return array_fill(0, $count, 1 / $count);
        }

        return array_map(fn ($m) => $m / $sum, $metrics);
    }

    private function postStock($grn): void
    {
        $grn->loadMissing(['inventory.branches', 'lines.product']);
        $rows = [];
        foreach ($grn->lines as $line) {
            $stockUomId = (int) ($line->product->stock_uom_id ?? $line->uom_id);
            $stockQty = $this->uomConversionService->convertToBaseUom((float) $line->good_quantity, (int) $line->uom_id, $stockUomId);
            $unitCost = (float) $line->final_unit_cost;
            $rows[] = [
                'transaction_date' => $grn->grn_date,
                'reference_type' => 'purchase_receive',
                'reference_id' => null,
                'voucher_no' => $grn->grn_no,
                'inventory_id' => $grn->inventory_id,
                'branch_name' => $this->stockLedgerService->resolveBranchName($grn->inventory),
                'movement_type' => 'in',
                'product_id' => $line->product_id,
                'sku' => $line->product->sku ?? '-',
                'lot_no' => null,
                'quantity' => $stockQty,
                'uom_id' => $stockUomId,
                'unit_cost' => $unitCost,
                'total_cost' => round($stockQty * $unitCost, 2),
            ];
        }

        $this->stockLedgerService->clearByReference('purchase_receive', null, $grn->grn_no);
        $this->stockLedgerService->addBulkStockLedger($rows);
    }

    private function postAccounting($grn, array $payload = []): void
    {
        $inventory = Account::where('code', '2-1024')->first();
        $apParent = Account::where('code', '4-2100')->first();
        $defectExpense = Account::where('code', '6-3000')->first();
        $supplierReceivable = Account::where('code', '2-1055')->first();
        if (!$inventory || !$apParent) {
            throw new Exception('Required accounts are missing.');
        }
        $supplierAp = $this->resolveSupplierApAccount($apParent->id, (string) $grn->supplier->name);

        $entry = JournalEntry::create([
            'journal_datetime' => now(),
            'source_type' => 'goods_receive_note',
            'source_id' => $grn->id,
            'description' => 'GRN posting for ' . $grn->grn_no,
        ]);

        $currencyId = (int) $grn->currency_id;
        $rate = (float) ($grn->currency->exchange_rate ?? 1);
        $total = (float) $grn->total_amount;

        JournalPosting::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $inventory->id,
            'type' => 'debit',
            'currency_id' => $currencyId,
            'amount' => $total,
            'base_currency_amount' => round($total * $rate, 8),
        ]);

        JournalPosting::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $supplierAp->id,
            'type' => 'credit',
            'currency_id' => $currencyId,
            'amount' => $total,
            'base_currency_amount' => round($total * $rate, 8),
        ]);

        foreach ($grn->lines as $line) {
            if ((float) $line->short_quantity <= 0) {
                continue;
            }
            $lossAmount = round(((float) $line->short_quantity) * ((float) $line->unit_price), 2);
            if ($lossAmount <= 0) {
                continue;
            }
            if ($line->discrepancy_reason === 'defect' && $line->defect_responsibility === 'company_side' && $defectExpense) {
                // Company side defect: recognize extra expense and payable.
                $this->postJournal($entry->id, $defectExpense->id, 'debit', $currencyId, $lossAmount, $rate);
                $this->postJournal($entry->id, $supplierAp->id, 'credit', $currencyId, $lossAmount, $rate);
            }
            if (($line->discrepancy_reason === 'cashback'
                || ($line->discrepancy_reason === 'defect' && $line->defect_responsibility === 'supplier_side'))
                && $supplierReceivable) {
                // Supplier side/cashback: reduce AP and book supplier claim receivable.
                $this->postJournal($entry->id, $supplierReceivable->id, 'debit', $currencyId, $lossAmount, $rate);
                $this->postJournal($entry->id, $supplierAp->id, 'credit', $currencyId, $lossAmount, $rate);
            }
        }

        $this->postCashbookPayment($grn, $entry->id, $supplierAp->id, $payload);
    }

    private function postCashbookPayment($grn, int $journalEntryId, int $supplierApAccountId, array $payload): void
    {
        $cashbookId = $payload['cashbook_id'] ?? null;
        if (!$cashbookId) {
            return;
        }
        $paidAmount = round((float) $grn->total_amount, 2);

        $cashbook = Cashbook::lockForUpdate()->find($cashbookId);
        if (!$cashbook) {
            throw new Exception('Cashbook not found.');
        }
        if ((int) $cashbook->currency_id !== (int) $grn->currency_id) {
            throw new Exception('Cashbook currency must match GRN currency.');
        }

        $lastLedger = CashbookLedger::where('cashbook_id', $cashbook->id)->orderByDesc('id')->lockForUpdate()->first();
        $beforeBalance = $lastLedger ? (float) $lastLedger->after_balance : (float) $cashbook->current_balance;
        $afterBalance = $beforeBalance - $paidAmount;
        $rate = (float) ($grn->currency->exchange_rate ?? 1);

        $trx = CashbookTransaction::create([
            'cashbook_id' => $cashbook->id,
            'source_account_id' => $cashbook->account_id,
            'destination_account_id' => $supplierApAccountId,
            'transaction_type' => 'out',
            'category' => 'expense',
            'transaction_datetime' => now(),
            'currency_id' => $grn->currency_id,
            'amount' => $paidAmount,
            'base_currency_amount' => round($paidAmount * $rate, 8),
            'reference_no' => 'GRNPAY-' . now()->format('YmdHis') . '-' . str_pad((string) $grn->id, 6, '0', STR_PAD_LEFT),
            'remark' => 'GRN Payment',
            'description' => 'Payment for GRN ' . $grn->grn_no,
            'status' => 'confirmed',
            'created_by' => auth()->id(),
        ]);

        CashbookLedger::create([
            'cashbook_id' => $cashbook->id,
            'cashbook_transaction_id' => $trx->id,
            'transaction_datetime' => now(),
            'transaction_type' => 'out',
            'amount' => $paidAmount,
            'before_balance' => $beforeBalance,
            'after_balance' => $afterBalance,
            'remark' => 'GRN Payment',
            'description' => 'Cashbook outflow for ' . $grn->grn_no,
        ]);

        $cashbook->update(['current_balance' => $afterBalance, 'updated_by' => auth()->id()]);

        JournalPosting::create([
            'journal_entry_id' => $journalEntryId,
            'account_id' => $supplierApAccountId,
            'type' => 'debit',
            'currency_id' => $grn->currency_id,
            'amount' => $paidAmount,
            'base_currency_amount' => round($paidAmount * $rate, 8),
        ]);
        JournalPosting::create([
            'journal_entry_id' => $journalEntryId,
            'account_id' => $cashbook->account_id,
            'type' => 'credit',
            'currency_id' => $grn->currency_id,
            'amount' => $paidAmount,
            'base_currency_amount' => round($paidAmount * $rate, 8),
        ]);
    }

    private function resolveSupplierApAccount(int $apParentId, string $supplierName)
    {
        $account = Account::where('parent_account_id', $apParentId)->where('name', $supplierName)->first();
        if ($account) {
            if (!$account->is_active) {
                $account->update(['is_active' => true]);
            }
            return $account;
        }

        return Account::create([
            'parent_account_id' => $apParentId,
            'code' => $this->accountRepository->generateAccountCode($apParentId),
            'name' => $supplierName,
            'type' => 'Accounts Payable',
            'division' => 'SOFP',
            'description' => 'Auto generated AP account for supplier ' . $supplierName,
            'is_active' => true,
        ]);
    }

    private function postJournal(int $entryId, int $accountId, string $type, int $currencyId, float $amount, float $rate): void
    {
        JournalPosting::create([
            'journal_entry_id' => $entryId,
            'account_id' => $accountId,
            'type' => $type,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'base_currency_amount' => round($amount * $rate, 8),
        ]);
    }

    private function updatePoDeliveryStatus(int $purchaseOrderId): void
    {
        $po = PurchaseOrder::with('lines')->find($purchaseOrderId);
        if (!$po) {
            return;
        }

        $ordered = (float) $po->lines->sum('quantity');
        $received = DB::table('goods_receive_notes_lines as gl')
            ->join('goods_receive_notes as g', 'g.id', '=', 'gl.goods_receive_note_id')
            ->where('g.purchase_order_id', $purchaseOrderId)
            ->where('g.status', 'approved')
            ->sum('gl.good_quantity');

        $status = 'not_delivered';
        if ($received > 0 && $received < $ordered) {
            $status = 'partially_delivered';
        } elseif ($ordered > 0 && $received >= $ordered) {
            $status = 'fully_delivered';
        }

        $po->update(['delivery_status' => $status]);
    }

    private function nextNumber(): string
    {
        $last = $this->repo->getLastRecord();
        $lastId = $last ? (int) substr($last->grn_no, strrpos($last->grn_no, '-') + 1) : 0;
        return 'INW-GRN-' . date('y-n-j') . '-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}
