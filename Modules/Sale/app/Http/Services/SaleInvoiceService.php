<?php

namespace Modules\Sale\app\Http\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\app\Http\Repositories\StockBalanceRepository;
use Modules\Inventory\app\Http\Services\StockLedgerService;
use Modules\Inventory\app\Http\Services\UOMConversionService;
use Modules\Inventory\app\Models\ProductLots;
use Modules\Organization\app\Models\Currency;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\Tax;
use Modules\Sale\app\Http\Repositories\SaleInvoiceRepository;
use Modules\Sale\app\Models\SaleInvoice;

class SaleInvoiceService
{
    protected $sale_invoice_repository;
    protected $stock_balance_repository;
    protected $stock_ledger_service;
    protected $uom_conversion_service;

    public function __construct(
        SaleInvoiceRepository $sale_invoice_repository,
        StockBalanceRepository $stock_balance_repository,
        StockLedgerService $stock_ledger_service,
        UOMConversionService $uom_conversion_service
    )
    {
        $this->sale_invoice_repository = $sale_invoice_repository;
        $this->stock_balance_repository = $stock_balance_repository;
        $this->stock_ledger_service = $stock_ledger_service;
        $this->uom_conversion_service = $uom_conversion_service;
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

                if ($status === 'delivered') {
                    $this->createSaleIssueMovements($existing, $context);
                }

                $updated = $this->sale_invoice_repository->update($id, ['status' => $status]);

                return ['status' => 'success', 'data' => $updated];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update sale invoice status: ' . $e->getMessage());
            throw $e;
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
        if ($deliveryChargePaid === 'receiver') {
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
            'ordered' => ['reserved', 'delivered'],
            'reserved' => ['ordered', 'delivered'],
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

    private function createSaleIssueMovements(SaleInvoice $invoice, array $context = []): void
    {
        $this->assertDeliveryAvailability($invoice, $context);
        $allocations = $this->resolveDeliveryAllocations($invoice, $context);

        $this->stock_ledger_service->addBulkStockLedger($allocations);
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
        $remainingByProduct = [];

        foreach ($requirements as $productId => $requirement) {
            $remainingByProduct[$productId] = (float) $requirement['quantity'];
        }

        if ($method === 'CUSTOM_BATCH' && empty($providedAllocations)) {
            throw new \RuntimeException('Custom batch delivery requires selected product lots.');
        }

        if (!empty($providedAllocations)) {
            foreach ($providedAllocations as $allocation) {
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

                $lotNo = $this->resolveLotNoFromAllocation($allocation, (int) $productId);

                if ($method === 'CUSTOM_BATCH' && $lotNo === null) {
                    throw new \RuntimeException('Custom batch delivery requires a selected product lot.');
                }

                $lotAvailableQuantity = $this->getLotAvailableQuantity((int) $invoice->inventory_id, $productId, $lotNo, $invoiceStatus);

                if ($lotNo !== null && !$this->allowsNegativeStock() && $stockQuantity > $lotAvailableQuantity + 0.00001) {
                    throw new \RuntimeException('Selected batch does not have enough quantity.');
                }

                $rows[] = $this->makeSaleIssueRow(
                    invoice: $invoice,
                    requirement: $requirements[$productId],
                    quantity: $stockQuantity,
                    lotNo: $lotNo
                );

                $remainingByProduct[$productId] -= $stockQuantity;
            }

            return $rows;
        }

        foreach ($requirements as $productId => $requirement) {
            $lotBalances = $this->stock_balance_repository->getLotBalancesForProduct((int) $invoice->inventory_id, (int) $productId);
            $sortedLots = $this->sortLotBalances($lotBalances, $method);
            $remaining = (float) $requirement['quantity'];

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
                    lotNo: $lotBalance['lot_no'] !== '' ? (string) $lotBalance['lot_no'] : null
                );

                $remaining -= $pickedQuantity;
            }

            if ($remaining > 0.00001 && !$this->allowsNegativeStock()) {
                throw new \RuntimeException('Insufficient stock for product ' . $requirement['product_name'] . '.');
            }
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
            $quantity = $this->convertQuantityToStockUom((float) $item->quantity, $sourceUomId, $stockUomId);

            if (!array_key_exists((int) $product->id, $requirements)) {
                $requirements[(int) $product->id] = [
                    'product_id' => (int) $product->id,
                    'product_name' => (string) $product->name,
                    'sku' => (string) $product->sku,
                    'quantity' => 0.0,
                    'uom_id' => (int) $item->uom_id,
                    'stock_uom_id' => $stockUomId,
                ];
            }

            $requirements[(int) $product->id]['quantity'] += $quantity;
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

        if (empty($allocation['product_lot_id'])) {
            return null;
        }

        $lot = ProductLots::query()
            ->where('id', (int) $allocation['product_lot_id'])
            ->where('product_id', $productId)
            ->first();

        if (!$lot) {
            throw new \RuntimeException('Selected product lot not found.');
        }

        return $lot->lot_no;
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

    private function makeSaleIssueRow(SaleInvoice $invoice, array $requirement, float $quantity, ?string $lotNo): array
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
            'transaction_date' => $invoice->invoice_date?->format('Y-m-d') ?? now()->toDateString(),
            'reference_type' => 'sale_issue',
            'reference_id' => null,
            'voucher_no' => $invoice->invoice_number,
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
}
