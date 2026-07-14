<?php

namespace Modules\Sale\app\Http\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\app\Http\Repositories\AccountRepository;
use Modules\Accounting\app\Models\Account;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Accounting\app\Models\CashbookLedger;
use Modules\Accounting\app\Models\CashbookTransaction;
use Modules\Accounting\app\Models\JournalEntry;
use Modules\Accounting\app\Models\JournalPosting;
use Modules\Inventory\app\Http\Repositories\StockBalanceRepository;
use Modules\Inventory\app\Http\Services\StockLedgerService;
use Modules\Inventory\app\Http\Services\UOMConversionService;
use Modules\Inventory\app\Models\ProductLots;
use Modules\Organization\app\Models\Currency;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\Tax;
use Modules\Sale\app\Http\Repositories\SaleInvoiceRepository;
use Modules\Sale\app\Models\DeliverNote;
use Modules\Sale\app\Models\SaleInvoice;

class SaleInvoiceService
{
    protected $sale_invoice_repository;
    protected $stock_balance_repository;
    protected $stock_ledger_service;
    protected $uom_conversion_service;
    protected $account_repository;

    public function __construct(
        SaleInvoiceRepository $sale_invoice_repository,
        StockBalanceRepository $stock_balance_repository,
        StockLedgerService $stock_ledger_service,
        UOMConversionService $uom_conversion_service,
        AccountRepository $account_repository
    )
    {
        $this->sale_invoice_repository = $sale_invoice_repository;
        $this->stock_balance_repository = $stock_balance_repository;
        $this->stock_ledger_service = $stock_ledger_service;
        $this->uom_conversion_service = $uom_conversion_service;
        $this->account_repository = $account_repository;
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        array|string|null $searches = null,
        array $conditions = [],
        ?string $status = null
    ) {
        try {
            return $this->sale_invoice_repository->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                searches: $searches,
                conditions: $conditions,
                with: [
                    'branch',
                    'inventory',
                    'customer',
                    'currency',
                    'selling_price_group',
                    'sell_tax',
                    'cashbook',
                    'delivery.delivery_provider',
                ],
                status: $status
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch sale invoice data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->sale_invoice_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch sale invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return DB::transaction(function () use ($attributes) {
                $normalized = $this->normalizePayload($attributes);
                $normalized['invoice']['invoice_number'] = $this->generateInvoiceNumber();
                $normalized['invoice']['payment_status'] = $this->resolvePaymentStatus(
                    (float) $normalized['invoice']['paid_amount'],
                    (float) $normalized['invoice']['grand_total']
                );

                return $this->sale_invoice_repository->createWithRelations(
                    $normalized['invoice'],
                    $normalized['items'],
                    $normalized['delivery']
                );
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create sale invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $existing = $this->sale_invoice_repository->find($id);
                if (!$existing) {
                    return ['status' => 'not_found'];
                }

                if (!in_array($existing->status, ['draft', 'pending'], true)) {
                    return ['status' => 'invalid_status'];
                }

                $normalized = $this->normalizePayload($attributes, $existing);
                $normalized['invoice']['payment_status'] = $this->resolvePaymentStatus(
                    (float) $normalized['invoice']['paid_amount'],
                    (float) $normalized['invoice']['grand_total']
                );

                $updated = $this->sale_invoice_repository->updateWithRelations(
                    $id,
                    $normalized['invoice'],
                    $normalized['items'],
                    $normalized['delivery']
                );

                return ['status' => 'success', 'data' => $updated];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update sale invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $existing = $this->sale_invoice_repository->find($id);
                if (!$existing) {
                    return ['status' => 'not_found'];
                }

                if (!in_array($existing->status, ['draft', 'pending'], true)) {
                    return ['status' => 'invalid_status'];
                }

                $this->sale_invoice_repository->deleteWithRelations($id);

                return ['status' => 'success'];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete sale invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    public function changeStatus(int $id, string $status, array $context = [])
    {
        try {
            return DB::transaction(function () use ($id, $status, $context) {
                $existing = $this->sale_invoice_repository->find($id);
                if (!$existing) {
                    return ['status' => 'not_found'];
                }

                if (!$this->isAllowedStatusTransition((string) $existing->status, $status)) {
                    return ['status' => 'invalid_transition'];
                }

                if ($status === 'reserved') {
                    $this->assertReservationAvailability($existing);
                }

                $this->syncInvoiceItemReservationMetrics($existing, $status);

                $updated = $this->sale_invoice_repository->update($id, ['status' => $status]);

                if (in_array($status, ['ordered', 'reserved'], true)) {
                    $this->postSaleInvoiceAccounting($updated);
                }

                return ['status' => 'success', 'data' => $updated];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update sale invoice status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncDeliveredStatusIfComplete(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $invoice = $this->sale_invoice_repository->find($id);
                if (!$invoice) {
                    return ['status' => 'not_found'];
                }

                $invoice->loadMissing('items');
                $allDelivered = $invoice->items->isNotEmpty()
                    && $invoice->items->every(function ($item) {
                        return round((float) ($item->remaining_delivery_qty ?? 0), 2) <= 0;
                    });

                if (!$allDelivered) {
                    return ['status' => 'not_complete'];
                }

                $updated = $this->sale_invoice_repository->update($id, ['status' => 'delivered']);

                return ['status' => 'success', 'data' => $updated];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to sync sale invoice delivered status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function issueDeliverNote(DeliverNote $deliverNote): array
    {
        $deliverNote->loadMissing('items');
        $invoice = SaleInvoice::with(['items.product'])->find($deliverNote->sale_invoice_id);

        if (!$invoice) {
            throw new \RuntimeException('Sale invoice not found.');
        }

        $usesInvoiceInventory = (int) $invoice->inventory_id === (int) $deliverNote->source_inventory_id;

        // A delivery note may override the invoice's default source inventory.
        $invoice->inventory_id = $deliverNote->source_inventory_id;
        if (!$usesInvoiceInventory && $invoice->status === 'reserved') {
            $invoice->status = 'ordered';
        }

        $allocations = $deliverNote->items->map(fn ($item) => [
            'product_id' => (int) $item->product_id,
            'uom_id' => (int) $item->uom_id,
            'quantity' => (float) $item->quantity,
        ])->all();

        return $this->createSaleIssueMovements($invoice, [
            'allocations' => $allocations,
            'transaction_date' => $deliverNote->delivery_date?->format('Y-m-d') ?? now()->toDateString(),
            'voucher_no' => $deliverNote->deliver_note_no,
        ]);
    }

    public function postDeliverNoteCogsAccounting(DeliverNote $deliverNote, array $stockRows): void
    {
        if ($this->journalEntryExists('deliver_note_cogs', (int) $deliverNote->id)) {
            return;
        }

        $amount = round(array_reduce($stockRows, function (float $total, array $row) {
            return $total + abs((float) ($row['total_cost'] ?? 0));
        }, 0.0), 8);

        if ($amount <= 0) {
            return;
        }

        $cogs = $this->resolveAccount('6-0000', 'COGS account (6-0000) is missing in COA.');
        $inventory = $this->resolveAccount('2-1024', 'Inventory account (2-1024) is missing in COA.');
        $currencyId = (int) $deliverNote->currency_id;
        $rate = $this->currencyRate((int) $currencyId);

        $this->createJournalEntry([
            'voucher_no' => $this->nextJournalVoucherNo(),
            'journal_date' => $deliverNote->delivery_date?->format('Y-m-d') ?? now()->toDateString(),
            'journal_datetime' => now(),
            'source_type' => 'deliver_note_cogs',
            'source_id' => $deliverNote->id,
            'description' => 'Deliver note COGS posting for ' . $deliverNote->deliver_note_no,
        ], [
            [
                'account_id' => $cogs->id,
                'type' => 'debit',
                'currency_id' => $currencyId,
                'amount' => $amount,
                'base_currency_amount' => round($amount * $rate, 8),
            ],
            [
                'account_id' => $inventory->id,
                'type' => 'credit',
                'currency_id' => $currencyId,
                'amount' => $amount,
                'base_currency_amount' => round($amount * $rate, 8),
            ],
        ]);
    }

    public function postDeliverNoteCashbookTransaction(DeliverNote $deliverNote): void
    {
        $deliverNote->loadMissing(['saleInvoice.customer', 'saleInvoice.delivery', 'saleInvoice.currency']);
        $invoice = $deliverNote->saleInvoice;
        $delivery = $invoice?->delivery;

        if (!$invoice || !$delivery) {
            return;
        }

        if ((string) $delivery->delivery_charge_paid !== 'shipper') {
            return;
        }

        $remainingReceivable = max((float) $invoice->grand_total - (float) $invoice->paid_amount, 0);
        $amount = round(min((float) $delivery->delivery_charge, $remainingReceivable), 8);
        if ($amount <= 0) {
            return;
        }

        if (empty($invoice->cashbook_id)) {
            throw new \RuntimeException('Cashbook is required to post deliver note delivery charge.');
        }

        $referenceNo = 'DNPAY-' . $invoice->invoice_number;
        $existingTransaction = CashbookTransaction::query()
            ->where('reference_no', $referenceNo)
            ->first();

        $arAccount = $this->resolveCustomerArAccount($invoice);
        $cashbook = Cashbook::query()->lockForUpdate()->find((int) $invoice->cashbook_id);
        if (!$cashbook) {
            throw new \RuntimeException('Cashbook not found.');
        }

        if ((int) $cashbook->currency_id !== (int) $deliverNote->currency_id) {
            throw new \RuntimeException('Cashbook currency must match deliver note currency.');
        }

        $rate = $this->currencyRate((int) $deliverNote->currency_id);
        if ($existingTransaction) {
            $this->ensureCashbookLedgerForTransaction($existingTransaction);
        } else {
            $this->createCashbookInflow(
                cashbook: $cashbook,
                sourceAccount: $arAccount,
                currencyId: (int) $deliverNote->currency_id,
                amount: $amount,
                rate: $rate,
                referenceNo: $referenceNo,
                remark: 'Deliver note delivery charge collection',
                description: 'Delivery charge collection for sale invoice ' . $invoice->invoice_number
            );
        }

        $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);
        $invoice->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $this->resolvePaymentStatus($newPaidAmount, (float) $invoice->grand_total),
        ]);

        if (!$this->journalEntryExists('sale_invoice_delivery_charge', (int) $invoice->id)) {
            $this->createJournalEntry([
                'voucher_no' => $this->nextJournalVoucherNo(),
                'journal_date' => $deliverNote->delivery_date?->format('Y-m-d') ?? now()->toDateString(),
                'journal_datetime' => now(),
                'source_type' => 'sale_invoice_delivery_charge',
                'source_id' => $invoice->id,
                'description' => 'Delivery charge collection for sale invoice ' . $invoice->invoice_number,
            ], [
                [
                    'account_id' => $cashbook->account_id,
                    'type' => 'debit',
                    'currency_id' => (int) $deliverNote->currency_id,
                    'amount' => $amount,
                    'base_currency_amount' => round($amount * $rate, 8),
                ],
                [
                    'account_id' => $arAccount->id,
                    'type' => 'credit',
                    'currency_id' => (int) $deliverNote->currency_id,
                    'amount' => $amount,
                    'base_currency_amount' => round($amount * $rate, 8),
                ],
            ]);
        }
    }

    private function normalizePayload(array $attributes, ?SaleInvoice $existing = null): array
    {
        $itemsInput = $attributes['items'] ?? ($existing ? $existing->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_type' => $item->discount_type ?? 'fixed',
                'discount_amount' => $item->discount,
                'order_qty' => $item->order_qty ?? $item->quantity,
                'reserved_qty' => $item->reserved_qty ?? 0,
                'previously_deliver_qty' => $item->previously_deliver_qty ?? 0,
                'remaining_delivery_qty' => max((float) ($item->order_qty ?? $item->quantity) - (float) ($item->previously_deliver_qty ?? 0), 0),
                'remarks' => $item->remarks,
            ];
        })->toArray() : []);

        if (empty($itemsInput)) {
            throw new \RuntimeException('At least one invoice item is required.');
        }

        $invoiceDate = Carbon::parse($attributes['invoice_date'] ?? $existing?->invoice_date ?? now());
        $paymentTerms = $attributes['payment_terms'] ?? $existing?->payment_terms ?? 'due_on_receipt';

        $branchId = $attributes['branch_id'] ?? $existing?->branch_id ?? auth()->user()?->branch?->id;
        if (empty($branchId)) {
            throw new \RuntimeException('Branch is required.');
        }

        $currencyId = (int) ($attributes['currency_id'] ?? $existing?->currency_id ?? 0);
        if ($currencyId <= 0) {
            throw new \RuntimeException('Currency is required.');
        }

        $currency = Currency::find($currencyId);
        if (!$currency) {
            throw new \RuntimeException('Currency not found.');
        }

        $sellTaxId = $attributes['sell_tax_id'] ?? $existing?->sell_tax_id ?? null;
        if (!$sellTaxId) {
            $firstProduct = Product::find($itemsInput[0]['product_id']);
            $sellTaxId = $firstProduct?->sale_tax_id;
        }
        if (!$sellTaxId) {
            throw new \RuntimeException('Sales tax is required.');
        }

        $sellTax = Tax::find($sellTaxId);
        $normalizedItems = $this->normalizeItems($itemsInput, $sellTax);

        $subTotal = 0.0;
        $itemsDiscount = 0.0;
        $taxTotal = 0.0;
        foreach ($normalizedItems as $item) {
            $subTotal += (float) $item['line_sub_total'];
            $itemsDiscount += (float) $item['discount'];
            $taxTotal += (float) $item['tax'];
        }

        $invoiceDiscountType = $attributes['invoice_discount_type'] ?? $existing?->invoice_discount_type ?? 'fixed';
        $invoiceDiscountAmount = (float) ($attributes['invoice_discount_amount'] ?? $existing?->invoice_discount_amount ?? 0);
        $invoiceDiscountValue = $this->calculateInvoiceDiscountValue($subTotal, $itemsDiscount, $invoiceDiscountType, $invoiceDiscountAmount);

        $deliveryInput = $attributes['delivery'] ?? ($existing?->delivery ? [
            'delivery_provider_id' => $existing->delivery->delivery_provider_id,
            'delivery_charge_paid' => $existing->delivery->delivery_charge_paid,
            'delivery_charge' => $existing->delivery->delivery_charge,
            'receiver_name' => $existing->delivery->receiver_name,
            'receiver_phone' => $existing->delivery->receiver_phone,
            'receiver_address' => $existing->delivery->receiver_address,
            'receiver_note' => $existing->delivery->receiver_note,
        ] : null);

        $deliveryData = null;
        $deliveryCharge = 0.0;
        $deliveryChargePaid = 'shipper';

        if (is_array($deliveryInput) && !empty($deliveryInput)) {
            if (empty($deliveryInput['delivery_provider_id'])) {
                throw new \RuntimeException('Delivery provider is required when delivery details are provided.');
            }

            $provider = \Modules\Sale\app\Models\DeliveryProvider::find($deliveryInput['delivery_provider_id']);
            if (!$provider) {
                throw new \RuntimeException('Delivery provider not found.');
            }

            $deliveryChargePaid = $deliveryInput['delivery_charge_paid'] ?? 'shipper';
            $deliveryCharge = (float) ($deliveryInput['delivery_charge'] ?? $provider->default_price ?? 0);

            $deliveryData = [
                'delivery_provider_id' => (int) $provider->id,
                'delivery_charge_paid' => $deliveryChargePaid,
                'delivery_charge' => round($deliveryCharge, 2),
                'receiver_name' => $deliveryInput['receiver_name'] ?? '',
                'receiver_phone' => $deliveryInput['receiver_phone'] ?? '',
                'receiver_address' => $deliveryInput['receiver_address'] ?? '',
                'receiver_note' => $deliveryInput['receiver_note'] ?? null,
            ];
        }

        $grandTotal = $subTotal - $itemsDiscount - $invoiceDiscountValue + $taxTotal;
        if ($deliveryChargePaid === 'shipper') {
            $grandTotal += $deliveryCharge;
        }
        $grandTotal = round($grandTotal, 2);

        $paidAmount = (float) ($attributes['paid_amount'] ?? $existing?->paid_amount ?? 0);
        if ($paidAmount > $grandTotal) {
            throw new \RuntimeException('Paid amount cannot exceed grand total.');
        }

        if ($paidAmount > 0 && empty($attributes['cashbook_id'] ?? $existing?->cashbook_id ?? null)) {
            throw new \RuntimeException('Cashbook is required when paid amount is greater than zero.');
        }

        $paymentDueDate = $attributes['payment_due_date'] ?? $existing?->payment_due_date ?? $this->calculateDueDate($invoiceDate, $paymentTerms);

        return [
            'invoice' => [
                'invoice_number' => $existing?->invoice_number,
                'invoice_date' => $invoiceDate->format('Y-m-d H:i:s'),
                'branch_id' => (int) $branchId,
                'inventory_id' => (int) ($attributes['inventory_id'] ?? $existing?->inventory_id),
                'customer_id' => (int) ($attributes['customer_id'] ?? $existing?->customer_id),
                'currency_id' => $currencyId,
                'exchange_rate' => (float) ($currency->exchange_rate ?? 1),
                'selling_price_group_id' => (int) ($attributes['selling_price_group_id'] ?? $existing?->selling_price_group_id),
                'inventory_transaction_method' => $this->normalizeInventoryTransactionMethod(
                    (string) ($attributes['inventory_transaction_method'] ?? $existing?->inventory_transaction_method ?? 'FIFO')
                ),
                'payment_terms' => $paymentTerms,
                'payment_due_date' => $paymentDueDate,
                'status' => in_array(($attributes['status'] ?? $existing?->status ?? 'draft'), ['draft', 'pending'], true)
                    ? ($attributes['status'] ?? $existing?->status ?? 'draft')
                    : 'draft',
                'remarks' => $attributes['remarks'] ?? $existing?->remarks ?? null,
                'sell_tax_id' => (int) $sellTaxId,
                'sub_total' => round($subTotal, 2),
                'items_discount' => round($itemsDiscount, 2),
                'invoice_discount_type' => $invoiceDiscountType,
                'invoice_discount_amount' => round($invoiceDiscountAmount, 2),
                'tax_total' => round($taxTotal, 2),
                'delivery_charge' => round($deliveryCharge, 2),
                'grand_total' => $grandTotal,
                'paid_amount' => round($paidAmount, 2),
                'cashbook_id' => $attributes['cashbook_id'] ?? $existing?->cashbook_id ?? null,
            ],
            'items' => $normalizedItems,
            'delivery' => $deliveryData,
        ];
    }

    private function normalizeItems(array $itemsInput, ?Tax $sellTax): array
    {
        $normalized = [];

        foreach ($itemsInput as $item) {
            $product = Product::with(['sale_tax', 'sale_uom'])->find($item['product_id'] ?? null);
            if (!$product) {
                throw new \RuntimeException('Product not found.');
            }

            $quantity = (float) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new \RuntimeException('Item quantity must be greater than zero.');
            }

            $unitPrice = (float) ($item['unit_price'] ?? $product->sale_price ?? 0);
            $uomId = $item['uom_id'] ?? $product->sale_uom_id ?? $product->stock_uom_id;
            if (empty($uomId)) {
                throw new \RuntimeException('Sales UOM is required for product ' . $product->name . '.');
            }

            $discountType = $item['discount_type'] ?? 'fixed';
            $discountAmountInput = (float) ($item['discount_amount'] ?? $item['discount'] ?? 0);
            $lineSubTotal = round($quantity * $unitPrice, 2);
            $discount = $discountType === 'percentage'
                ? round($lineSubTotal * ($discountAmountInput / 100), 2)
                : round($discountAmountInput, 2);

            if ($discount > $lineSubTotal) {
                throw new \RuntimeException('Item discount cannot exceed the line subtotal.');
            }

            $taxRate = (float) ($sellTax?->amount ?? $product->sale_tax?->amount ?? 0);
            $tax = round(($lineSubTotal - $discount) * ($taxRate / 100), 2);
            $lineTotal = round($lineSubTotal - $discount + $tax, 2);

            $normalized[] = [
                'product_id' => (int) $product->id,
                'uom_id' => (int) $uomId,
                'quantity' => $quantity,
                'order_qty' => round($quantity, 2),
                'reserved_qty' => 0,
                'previously_deliver_qty' => 0,
                'remaining_delivery_qty' => round($quantity, 2),
                'unit_price' => round($unitPrice, 2),
                'discount_type' => $discountType,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
                'line_sub_total' => $lineSubTotal,
            ];
        }

        return $normalized;
    }

    private function calculateInvoiceDiscountValue(float $subTotal, float $itemsDiscount, string $type, float $amount): float
    {
        $baseAmount = max($subTotal - $itemsDiscount, 0);

        if ($type === 'percentage') {
            if ($amount > 100) {
                throw new \RuntimeException('Invoice discount percentage cannot exceed 100.');
            }

            return round($baseAmount * ($amount / 100), 2);
        }

        if ($amount > $baseAmount) {
            throw new \RuntimeException('Invoice discount cannot exceed the invoice base amount.');
        }

        return round($amount, 2);
    }

    private function calculateDueDate(Carbon $invoiceDate, string $paymentTerms): string
    {
        return match ($paymentTerms) {
            'net30' => $invoiceDate->copy()->addDays(30)->toDateString(),
            'net60' => $invoiceDate->copy()->addDays(60)->toDateString(),
            default => $invoiceDate->copy()->toDateString(),
        };
    }

    private function resolvePaymentStatus(float $paidAmount, float $grandTotal): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if (round($paidAmount, 2) >= round($grandTotal, 2) && $grandTotal > 0) {
            return 'paid';
        }

        return 'partial';
    }

    private function isAllowedStatusTransition(string $current, string $next): bool
    {
        $allowed = [
            'draft' => ['pending'],
            'pending' => ['draft', 'ordered'],
            'ordered' => ['reserved'],
            'reserved' => ['ordered'],
            'delivered' => [],
        ];

        return in_array($next, $allowed[$current] ?? [], true);
    }

    private function assertReservationAvailability(SaleInvoice $invoice): void
    {
        $requirements = $this->getInvoiceProductRequirements($invoice);

        foreach ($requirements as $productId => $requirement) {
            $summary = $this->stock_balance_repository->getProductSummary((int) $invoice->inventory_id, (int) $productId);
            if ((float) $summary['available_quantity'] >= (float) $requirement['quantity']) {
                continue;
            }

            if ($this->allowsNegativeStock()) {
                continue;
            }

            throw new \RuntimeException(
                'Insufficient available stock for product ' . $requirement['product_name'] . '.'
            );
        }
    }

    private function createSaleIssueMovements(SaleInvoice $invoice, array $context = []): array
    {
        $this->assertDeliveryAvailability($invoice, $context);
        $allocations = $this->resolveDeliveryAllocations($invoice, $context);

        $this->stock_ledger_service->addBulkStockLedger($allocations);

        return $allocations;
    }

    private function postSaleInvoiceAccounting(SaleInvoice $invoice): void
    {
        $invoice->loadMissing(['customer', 'currency']);

        $arAccount = $this->resolveCustomerArAccount($invoice);
        $salesIncome = $this->resolveAccount('5-0000', 'Sales income account (5-0000) is missing in COA.');
        $currencyId = (int) $invoice->currency_id;
        $amount = round((float) $invoice->grand_total, 8);
        $rate = $this->invoiceCurrencyRate($invoice);

        if ($amount <= 0) {
            return;
        }

        if (!$this->journalEntryExists('sale_invoice_revenue', (int) $invoice->id)) {
            $this->createJournalEntry([
                'voucher_no' => $this->nextJournalVoucherNo(),
                'journal_date' => $invoice->invoice_date?->format('Y-m-d') ?? now()->toDateString(),
                'journal_datetime' => now(),
                'source_type' => 'sale_invoice_revenue',
                'source_id' => $invoice->id,
                'description' => 'Sale invoice revenue posting for ' . $invoice->invoice_number,
            ], [
                [
                    'account_id' => $arAccount->id,
                    'type' => 'debit',
                    'currency_id' => $currencyId,
                    'amount' => $amount,
                    'base_currency_amount' => round($amount * $rate, 8),
                ],
                [
                    'account_id' => $salesIncome->id,
                    'type' => 'credit',
                    'currency_id' => $currencyId,
                    'amount' => $amount,
                    'base_currency_amount' => round($amount * $rate, 8),
                ],
            ]);
        }

        $this->postSaleInvoiceCashbookReceipt($invoice, $arAccount);
    }

    private function postSaleInvoiceCashbookReceipt(SaleInvoice $invoice, Account $arAccount): void
    {
        $paidAmount = round((float) $invoice->paid_amount, 8);
        if ($paidAmount <= 0) {
            return;
        }

        if (empty($invoice->cashbook_id)) {
            throw new \RuntimeException('Cashbook is required when paid amount is greater than zero.');
        }

        $referenceNo = 'SIPAY-' . $invoice->invoice_number;
        $existingTransaction = CashbookTransaction::query()
            ->where('reference_no', $referenceNo)
            ->first();

        $cashbook = Cashbook::query()->lockForUpdate()->find((int) $invoice->cashbook_id);
        if (!$cashbook) {
            throw new \RuntimeException('Cashbook not found.');
        }

        if ((int) $cashbook->currency_id !== (int) $invoice->currency_id) {
            throw new \RuntimeException('Cashbook currency must match sale invoice currency.');
        }

        $rate = $this->invoiceCurrencyRate($invoice);
        if ($existingTransaction) {
            $this->ensureCashbookLedgerForTransaction($existingTransaction);
        } else {
            $this->createCashbookInflow(
                cashbook: $cashbook,
                sourceAccount: $arAccount,
                currencyId: (int) $invoice->currency_id,
                amount: $paidAmount,
                rate: $rate,
                referenceNo: $referenceNo,
                remark: 'Sale invoice receipt',
                description: 'Receipt for sale invoice ' . $invoice->invoice_number
            );
        }

        if (!$this->journalEntryExists('sale_invoice_payment', (int) $invoice->id)) {
            $this->createJournalEntry([
                'voucher_no' => $this->nextJournalVoucherNo(),
                'journal_date' => $invoice->invoice_date?->format('Y-m-d') ?? now()->toDateString(),
                'journal_datetime' => now(),
                'source_type' => 'sale_invoice_payment',
                'source_id' => $invoice->id,
                'description' => 'Sale invoice receipt for ' . $invoice->invoice_number,
            ], [
                [
                    'account_id' => $cashbook->account_id,
                    'type' => 'debit',
                    'currency_id' => (int) $invoice->currency_id,
                    'amount' => $paidAmount,
                    'base_currency_amount' => round($paidAmount * $rate, 8),
                ],
                [
                    'account_id' => $arAccount->id,
                    'type' => 'credit',
                    'currency_id' => (int) $invoice->currency_id,
                    'amount' => $paidAmount,
                    'base_currency_amount' => round($paidAmount * $rate, 8),
                ],
            ]);
        }
    }

    private function resolveCustomerArAccount(SaleInvoice $invoice): Account
    {
        $parent = $this->resolveAccount('2-2000', 'Accounts receivable parent account (2-2000) is missing in COA.');
        $customerName = trim((string) ($invoice->customer?->name ?? $invoice->customer?->company_name ?? 'Customer-' . $invoice->customer_id));

        $account = Account::query()
            ->where('parent_account_id', $parent->id)
            ->where('name', $customerName)
            ->first();

        if ($account) {
            if (!$account->is_active) {
                $account->update(['is_active' => true]);
            }

            return $account;
        }

        return Account::create([
            'parent_account_id' => $parent->id,
            'code' => $this->account_repository->generateAccountCode($parent->id),
            'name' => $customerName,
            'type' => 'Accounts Receivable',
            'division' => 'SOFP',
            'description' => 'Auto generated AR account for customer ' . $customerName,
            'is_active' => true,
        ]);
    }

    private function resolveAccount(string $code, string $missingMessage): Account
    {
        $account = Account::where('code', $code)->first();
        if (!$account) {
            throw new \RuntimeException($missingMessage);
        }

        return $account;
    }

    private function createJournalEntry(array $header, array $postings): JournalEntry
    {
        $journalEntry = JournalEntry::create([
            'voucher_no' => $header['voucher_no'] ?? $this->nextJournalVoucherNo(),
            'journal_date' => $header['journal_date'] ?? now()->toDateString(),
            'journal_datetime' => $header['journal_datetime'] ?? now(),
            'source_type' => $header['source_type'],
            'source_id' => $header['source_id'],
            'description' => $header['description'] ?? null,
        ]);

        foreach ($postings as $posting) {
            JournalPosting::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $posting['account_id'],
                'type' => $posting['type'],
                'currency_id' => $posting['currency_id'],
                'amount' => $posting['amount'],
                'base_currency_amount' => $posting['base_currency_amount'],
            ]);
        }

        return $journalEntry;
    }

    private function createCashbookInflow(
        Cashbook $cashbook,
        Account $sourceAccount,
        int $currencyId,
        float $amount,
        float $rate,
        string $referenceNo,
        string $remark,
        string $description
    ): CashbookTransaction {
        $lastLedger = CashbookLedger::query()
            ->where('cashbook_id', $cashbook->id)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        $beforeBalance = $lastLedger ? (float) $lastLedger->after_balance : (float) $cashbook->current_balance;
        $afterBalance = $beforeBalance + $amount;

        $transaction = CashbookTransaction::create([
            'cashbook_id' => $cashbook->id,
            'source_account_id' => $sourceAccount->id,
            'destination_account_id' => $cashbook->account_id,
            'transaction_type' => 'in',
            'category' => 'income',
            'transaction_datetime' => now(),
            'currency_id' => $currencyId,
            'amount' => $amount,
            'base_currency_amount' => round($amount * $rate, 8),
            'reference_no' => $referenceNo,
            'remark' => $remark,
            'description' => $description,
            'status' => 'confirmed',
            'created_by' => auth()->id(),
        ]);

        CashbookLedger::create([
            'cashbook_id' => $cashbook->id,
            'cashbook_transaction_id' => $transaction->id,
            'transaction_datetime' => now(),
            'transaction_type' => 'in',
            'amount' => $amount,
            'before_balance' => $beforeBalance,
            'after_balance' => $afterBalance,
            'remark' => $remark,
            'description' => 'Cashbook inflow for ' . $referenceNo,
        ]);

        $cashbook->update([
            'current_balance' => $afterBalance,
            'updated_by' => auth()->id(),
        ]);

        return $transaction;
    }

    private function ensureCashbookLedgerForTransaction(CashbookTransaction $transaction): void
    {
        if (CashbookLedger::query()->where('cashbook_transaction_id', $transaction->id)->exists()) {
            return;
        }

        $cashbook = Cashbook::query()->lockForUpdate()->find((int) $transaction->cashbook_id);
        if (!$cashbook) {
            throw new \RuntimeException('Cashbook not found.');
        }

        $lastLedger = CashbookLedger::query()
            ->where('cashbook_id', $cashbook->id)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        $beforeBalance = $lastLedger ? (float) $lastLedger->after_balance : (float) $cashbook->current_balance;
        $amount = (float) $transaction->amount;
        $afterBalance = $transaction->transaction_type === 'in'
            ? $beforeBalance + $amount
            : $beforeBalance - $amount;

        CashbookLedger::create([
            'cashbook_id' => $cashbook->id,
            'cashbook_transaction_id' => $transaction->id,
            'transaction_datetime' => now(),
            'transaction_type' => $transaction->transaction_type,
            'amount' => $amount,
            'before_balance' => $beforeBalance,
            'after_balance' => $afterBalance,
            'remark' => $transaction->remark,
            'description' => 'Cashbook ledger for ' . $transaction->reference_no,
        ]);

        $cashbook->update([
            'current_balance' => $afterBalance,
            'updated_by' => auth()->id(),
        ]);
    }

    private function journalEntryExists(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    private function nextJournalVoucherNo(): string
    {
        $voucherPrefix = 'JV-' . now()->format('Ymd') . '-';
        $lastVoucher = JournalEntry::where('voucher_no', 'like', $voucherPrefix . '%')
            ->orderByDesc('id')
            ->value('voucher_no');

        $nextVoucherSeq = 1;
        if ($lastVoucher) {
            $nextVoucherSeq = ((int) substr($lastVoucher, -4)) + 1;
        }

        return $voucherPrefix . str_pad((string) $nextVoucherSeq, 4, '0', STR_PAD_LEFT);
    }

    private function invoiceCurrencyRate(SaleInvoice $invoice): float
    {
        return (float) ($invoice->exchange_rate ?: $invoice->currency?->exchange_rate ?: 1);
    }

    private function currencyRate(int $currencyId): float
    {
        $currency = Currency::find($currencyId);

        return (float) ($currency?->exchange_rate ?: 1);
    }

    private function assertDeliveryAvailability(SaleInvoice $invoice, array $context = []): void
    {
        $requirements = $this->getInvoiceProductRequirements($invoice);
        $requestedQuantities = $this->getRequestedDeliveryQuantities($invoice, $context, $requirements);
        $invoiceStatus = (string) $invoice->status;

        foreach ($requirements as $productId => $requirement) {
            $summary = $this->stock_balance_repository->getProductSummary((int) $invoice->inventory_id, (int) $productId);
            $availableQuantity = (float) $summary['available_quantity'];

            if ($invoiceStatus === 'reserved') {
                $availableQuantity += (float) $requirement['quantity'];
            }

            if ($this->allowsNegativeStock()) {
                continue;
            }

            if (($requestedQuantities[$productId] ?? 0) > $availableQuantity + 0.00001) {
                throw new \RuntimeException(
                    'Insufficient stock available to deliver product ' . $requirement['product_name'] . '.'
                );
            }
        }
    }

    private function getRequestedDeliveryQuantities(SaleInvoice $invoice, array $context, array $requirements): array
    {
        $requestedQuantities = [];
        $providedAllocations = $context['allocations'] ?? $context['delivery_allocations'] ?? [];

        if (!empty($providedAllocations)) {
            foreach ($providedAllocations as $allocation) {
                $productId = (int) ($allocation['product_id'] ?? 0);
                if (!array_key_exists($productId, $requirements)) {
                    continue;
                }

                $quantity = (float) ($allocation['quantity'] ?? 0);
                $uomId = (int) ($allocation['uom_id'] ?? $requirements[$productId]['uom_id']);
                $stockUomId = (int) $requirements[$productId]['stock_uom_id'];
                $stockQuantity = $this->convertQuantityToStockUom($quantity, $uomId, $stockUomId);
                $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + $stockQuantity;
            }

            return $requestedQuantities;
        }

        foreach ($requirements as $productId => $requirement) {
            $requestedQuantities[$productId] = (float) $requirement['quantity'];
        }

        return $requestedQuantities;
    }

    private function resolveDeliveryAllocations(SaleInvoice $invoice, array $context = []): array
    {
        $requirements = $this->getInvoiceProductRequirements($invoice);
        $invoiceStatus = (string) $invoice->status;
        $method = strtoupper((string) ($context['inventory_transaction_method'] ?? $invoice->inventory_transaction_method ?? 'FIFO'));
        if (!in_array($method, ['FIFO', 'LIFO', 'CUSTOM_BATCH'], true)) {
            $method = 'FIFO';
        }

        $providedAllocations = $context['allocations'] ?? $context['delivery_allocations'] ?? [];
        $rows = [];

        if ($method === 'CUSTOM_BATCH' && empty($providedAllocations)) {
            throw new \RuntimeException('Custom batch delivery requires selected product lots.');
        }

        if (!empty($providedAllocations)) {
            return $this->makeSaleIssueRowsFromProvidedAllocations(
                invoice: $invoice,
                allocations: $providedAllocations,
                requirements: $requirements,
                method: $method,
                invoiceStatus: $invoiceStatus,
                context: $context
            );
        }

        foreach ($requirements as $productId => $requirement) {
            $rows = array_merge(
                $rows,
                $this->makeSaleIssueRowsFromAvailableLots(
                    invoice: $invoice,
                    requirement: $requirement,
                    quantity: (float) $requirement['quantity'],
                    method: $method,
                    invoiceStatus: $invoiceStatus,
                    context: $context
                )
            );
        }

        return $rows;
    }

    private function makeSaleIssueRowsFromProvidedAllocations(
        SaleInvoice $invoice,
        array $allocations,
        array $requirements,
        string $method,
        string $invoiceStatus,
        array $context = []
    ): array
    {
        $remainingByProduct = [];
        $autoAllocatedQuantities = [];
        $explicitLotAllocatedQuantities = [];

        foreach ($requirements as $productId => $requirement) {
            $remainingByProduct[$productId] = (float) $requirement['quantity'];
        }

        foreach ($allocations as $allocation) {
            $productId = (int) ($allocation['product_id'] ?? 0);
            if (!array_key_exists($productId, $requirements)) {
                throw new \RuntimeException('Delivery allocation contains an unknown product.');
            }

            $quantity = (float) ($allocation['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new \RuntimeException('Delivery allocation quantity must be greater than zero.');
            }

            $uomId = (int) ($allocation['uom_id'] ?? $requirements[$productId]['uom_id']);
            $stockUomId = (int) $requirements[$productId]['stock_uom_id'];
            $stockQuantity = $this->convertQuantityToStockUom($quantity, $uomId, $stockUomId);

            if (!$this->allowsNegativeStock() && $stockQuantity > $remainingByProduct[$productId] + 0.00001) {
                throw new \RuntimeException(
                    'Delivery quantity exceeds the invoice quantity for product ' . $requirements[$productId]['product_name'] . '.'
                );
            }

            $lotNo = $this->resolveLotNoFromAllocation($allocation, $productId);
            if ($method === 'CUSTOM_BATCH' && $lotNo === null) {
                throw new \RuntimeException('Custom batch delivery requires a selected product lot.');
            }

            if ($lotNo === null) {
                $autoAllocatedQuantities[$productId] = ($autoAllocatedQuantities[$productId] ?? 0) + $stockQuantity;
            } else {
                $key = $productId . '|' . $lotNo;
                if (!isset($explicitLotAllocatedQuantities[$key])) {
                    $explicitLotAllocatedQuantities[$key] = [
                        'product_id' => $productId,
                        'lot_no' => $lotNo,
                        'quantity' => 0.0,
                    ];
                }
                $explicitLotAllocatedQuantities[$key]['quantity'] += $stockQuantity;
            }

            $remainingByProduct[$productId] -= $stockQuantity;
        }

        $rows = [];

        foreach ($explicitLotAllocatedQuantities as $allocation) {
            $productId = (int) $allocation['product_id'];
            $lotNo = (string) $allocation['lot_no'];
            $stockQuantity = (float) $allocation['quantity'];
            $lotAvailableQuantity = $this->getLotAvailableQuantity((int) $invoice->inventory_id, $productId, $lotNo, $invoiceStatus);

            if (!$this->allowsNegativeStock() && $stockQuantity > $lotAvailableQuantity + 0.00001) {
                throw new \RuntimeException('Selected batch does not have enough quantity.');
            }

            $rows[] = $this->makeSaleIssueRow(
                invoice: $invoice,
                requirement: $requirements[$productId],
                quantity: $stockQuantity,
                lotNo: $lotNo,
                context: $context
            );
        }

        foreach ($autoAllocatedQuantities as $productId => $stockQuantity) {
            $rows = array_merge(
                $rows,
                $this->makeSaleIssueRowsFromAvailableLots(
                    invoice: $invoice,
                    requirement: $requirements[$productId],
                    quantity: (float) $stockQuantity,
                    method: $method,
                    invoiceStatus: $invoiceStatus,
                    context: $context
                )
            );
        }

        return $rows;
    }

    private function makeSaleIssueRowsFromAvailableLots(
        SaleInvoice $invoice,
        array $requirement,
        float $quantity,
        string $method,
        string $invoiceStatus,
        array $context = []
    ): array
    {
        $rows = [];
        $remaining = $quantity;
        $lotBalances = $this->stock_balance_repository->getLotBalancesForProduct(
            (int) $invoice->inventory_id,
            (int) $requirement['product_id']
        );
        $sortedLots = $this->sortLotBalances($lotBalances, $method);

        foreach ($sortedLots as $lotBalance) {
            if ($remaining <= 0) {
                break;
            }

            $availableQuantity = (float) ($lotBalance['available_quantity'] ?? $lotBalance['on_hand_quantity'] ?? 0);
            if ($invoiceStatus === 'reserved') {
                $availableQuantity = (float) ($lotBalance['on_hand_quantity'] ?? 0);
            }

            if ($availableQuantity <= 0) {
                continue;
            }

            $pickedQuantity = min($remaining, $availableQuantity);
            $rows[] = $this->makeSaleIssueRow(
                invoice: $invoice,
                requirement: $requirement,
                quantity: $pickedQuantity,
                lotNo: $lotBalance['lot_no'] !== '' ? (string) $lotBalance['lot_no'] : null,
                context: $context
            );

            $remaining -= $pickedQuantity;
        }

        if ($remaining > 0.00001) {
            if (!$this->allowsNegativeStock()) {
                throw new \RuntimeException('Insufficient stock for product ' . $requirement['product_name'] . '.');
            }

            $rows[] = $this->makeSaleIssueRow(
                invoice: $invoice,
                requirement: $requirement,
                quantity: $remaining,
                lotNo: null,
                context: $context
            );
        }

        return $rows;
    }

    private function getInvoiceProductRequirements(SaleInvoice $invoice): array
    {
        $requirements = [];

        foreach ($invoice->items as $item) {
            $product = $item->product;
            if (!$product) {
                throw new \RuntimeException('Product not found for one of the invoice items.');
            }

            $stockUomId = (int) ($product->stock_uom_id ?? $item->uom_id);
            $sourceUomId = (int) ($item->uom_id ?? $stockUomId);
            $orderQty = (float) ($item->order_qty ?? $item->quantity ?? 0);
            $quantity = $this->convertQuantityToStockUom($orderQty, $sourceUomId, $stockUomId);

            if (!array_key_exists((int) $product->id, $requirements)) {
                $requirements[(int) $product->id] = [
                    'product_id' => (int) $product->id,
                    'product_name' => (string) $product->name,
                    'sku' => (string) $product->sku,
                    'quantity' => 0.0,
                    'order_qty' => 0.0,
                    'reserved_qty' => 0.0,
                    'previously_deliver_qty' => 0.0,
                    'remaining_delivery_qty' => 0.0,
                    'uom_id' => (int) $item->uom_id,
                    'stock_uom_id' => $stockUomId,
                ];
            }

            $requirements[(int) $product->id]['quantity'] += $quantity;
            $requirements[(int) $product->id]['order_qty'] += $orderQty;
            $requirements[(int) $product->id]['reserved_qty'] += (float) ($item->reserved_qty ?? 0);
            $requirements[(int) $product->id]['previously_deliver_qty'] += (float) ($item->previously_deliver_qty ?? 0);
            $requirements[(int) $product->id]['remaining_delivery_qty'] += (float) (
                $item->remaining_delivery_qty ?? max($orderQty - (float) ($item->previously_deliver_qty ?? 0), 0)
            );
        }

        return $requirements;
    }

    private function convertQuantityToStockUom(float $quantity, int $fromUomId, int $stockUomId): float
    {
        if ($fromUomId === $stockUomId) {
            return round($quantity, 4);
        }

        return round($this->uom_conversion_service->convertToBaseUom($quantity, $fromUomId, $stockUomId), 4);
    }

    private function resolveLotNoFromAllocation(array $allocation, int $productId): ?string
    {
        if (!empty($allocation['lot_no'])) {
            $lotNo = (string) $allocation['lot_no'];
            $lot = ProductLots::query()
                ->where('product_id', $productId)
                ->where('lot_no', $lotNo)
                ->first();

            if (!$lot) {
                throw new \RuntimeException('Selected product lot not found.');
            }

            return $lotNo;
        }

        return null;
    }

    private function getLotAvailableQuantity(int $inventoryId, int $productId, ?string $lotNo, string $invoiceStatus): float
    {
        $lotBalances = $this->stock_balance_repository->getLotBalancesForProduct($inventoryId, $productId);

        foreach ($lotBalances as $lotBalance) {
            $currentLotNo = (string) ($lotBalance['lot_no'] ?? '');
            if (($lotNo === null && $currentLotNo === '') || ($lotNo !== null && $currentLotNo === $lotNo)) {
                if ($invoiceStatus === 'reserved') {
                    return (float) ($lotBalance['on_hand_quantity'] ?? 0);
                }

                return (float) ($lotBalance['available_quantity'] ?? 0);
            }
        }

        return 0.0;
    }

    private function sortLotBalances(array $lotBalances, string $method): array
    {
        usort($lotBalances, function (array $left, array $right) use ($method) {
            $leftDate = strtotime((string) ($left['created_at'] ?? $left['last_movement_date'] ?? '1970-01-01 00:00:00'));
            $rightDate = strtotime((string) ($right['created_at'] ?? $right['last_movement_date'] ?? '1970-01-01 00:00:00'));

            if ($method === 'LIFO') {
                return $rightDate <=> $leftDate;
            }

            return $leftDate <=> $rightDate;
        });

        return $lotBalances;
    }

    private function makeSaleIssueRow(
        SaleInvoice $invoice,
        array $requirement,
        float $quantity,
        ?string $lotNo,
        array $context = []
    ): array
    {
        $product = Product::query()->find((int) $requirement['product_id']);
        if (!$product) {
            throw new \RuntimeException('Product not found.');
        }

        $stockUomId = (int) ($requirement['stock_uom_id'] ?? $product->stock_uom_id ?? $requirement['uom_id']);
        $currentBalance = $this->stock_ledger_service->getRunningBalances(
            (string) $product->sku,
            (int) $invoice->inventory_id,
            $lotNo
        );

        $quantityBefore = (float) $currentBalance['quantity_before'];
        $costBefore = (float) $currentBalance['cost_before'];
        $unitCost = $quantityBefore > 0 ? ($costBefore / $quantityBefore) : (float) ($product->purchase_price ?? 0);

        return [
            'transaction_date' => $context['transaction_date']
                ?? $invoice->invoice_date?->format('Y-m-d')
                ?? now()->toDateString(),
            'reference_type' => 'sale_issue',
            'reference_id' => null,
            'voucher_no' => $context['voucher_no'] ?? $invoice->invoice_number,
            'product_id' => (int) $product->id,
            'sku' => (string) $product->sku,
            'lot_no' => $lotNo,
            'inventory_id' => (int) $invoice->inventory_id,
            'movement_type' => 'out',
            'quantity' => round($quantity, 4),
            'uom_id' => $stockUomId,
            'unit_cost' => round($unitCost, 4),
            'total_cost' => round($quantity * $unitCost, 4),
        ];
    }

    private function allowsNegativeStock(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        foreach ([
            'allow_negative_stock',
            'allow_negative_inventory',
            'negative_stock',
        ] as $permissionKey) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission($permissionKey)) {
                return true;
            }
        }

        return false;
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'SI-' . now()->format('Ymd') . '-';
        $lastNumber = SaleInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $nextSequence = 1;
        if ($lastNumber) {
            $nextSequence = ((int) substr($lastNumber, strrpos($lastNumber, '-') + 1)) + 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    private function normalizeInventoryTransactionMethod(string $method): string
    {
        $normalized = strtoupper(trim($method));

        return match ($normalized) {
            'LIFO' => 'LIFO',
            'CUSTOM_BATCH' => 'custom_batch',
            default => 'FIFO',
        };
    }

    private function syncInvoiceItemReservationMetrics(SaleInvoice $invoice, string $status): void
    {
        if (!in_array($status, ['ordered', 'reserved'], true)) {
            return;
        }

        $items = $invoice->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        if ($status === 'reserved') {
            foreach ($items as $item) {
                $orderQty = (float) ($item->order_qty ?? $item->quantity ?? 0);
                $previouslyDelivered = (float) ($item->previously_deliver_qty ?? 0);

                $item->update([
                    'order_qty' => round($orderQty, 2),
                    'reserved_qty' => round(max($orderQty - $previouslyDelivered, 0), 2),
                    'previously_deliver_qty' => round($previouslyDelivered, 2),
                    'remaining_delivery_qty' => round(max($orderQty - $previouslyDelivered, 0), 2),
                ]);
            }

            return;
        }

        foreach ($items as $item) {
            $orderQty = (float) ($item->order_qty ?? $item->quantity ?? 0);
            $previouslyDelivered = (float) ($item->previously_deliver_qty ?? 0);

            $item->update([
                'order_qty' => round($orderQty, 2),
                'reserved_qty' => 0,
                'previously_deliver_qty' => round($previouslyDelivered, 2),
                'remaining_delivery_qty' => round(max($orderQty - $previouslyDelivered, 0), 2),
            ]);
        }
    }

}
