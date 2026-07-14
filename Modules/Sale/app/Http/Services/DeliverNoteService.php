<?php

namespace Modules\Sale\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Sale\app\Http\Repositories\DeliverNoteRepository;
use Modules\Sale\app\Models\DeliverNote;
use Modules\Sale\app\Models\SaleInvoice;
use Modules\Sale\app\Http\Services\SaleInvoiceService;

class DeliverNoteService
{
    public function __construct(
        protected DeliverNoteRepository $repository,
        protected SaleInvoiceService $saleInvoiceService
    ) {
    }

    public function getDataWithPagination(
        int $perPage = 10,
        int $page = 1,
        array|string|null $searches = null,
        array $conditions = [],
        ?string $status = null
    ) {
        try {
            return $this->repository->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                searches: $searches,
                conditions: $conditions,
                with: [],
                status: $status
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch deliver note data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch deliver note: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            return DB::transaction(function () use ($attributes) {
                $normalized = $this->normalizePayload($attributes);
                $normalized['header']['deliver_note_no'] = $this->generateDeliverNoteNumber();
                $normalized['header']['status'] = 'draft';

                return $this->repository->createWithRelations(
                    $normalized['header'],
                    $normalized['items']
                );
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to create deliver note: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            return DB::transaction(function () use ($id, $attributes) {
                $existing = $this->repository->find($id);
                if (!$existing) {
                    return ['status' => 'not_found'];
                }

                if (!in_array($existing->status, ['draft', 'pending'], true)) {
                    return ['status' => 'invalid_status'];
                }

                $normalized = $this->normalizePayload($attributes, $existing);
                $normalized['header']['deliver_note_no'] = $existing->deliver_note_no;
                $normalized['header']['status'] = $existing->status;

                $updated = $this->repository->updateWithRelations($id, $normalized['header'], $normalized['items']);

                return ['status' => 'success', 'data' => $updated];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update deliver note: ' . $e->getMessage());
            throw $e;
        }
    }

    public function changeStatus(int $id, string $status)
    {
        try {
            return DB::transaction(function () use ($id, $status) {
                $existing = $this->repository->find($id);
                if (!$existing) {
                    return ['status' => 'not_found'];
                }

                $currentStatus = (string) $existing->status;
                if (!$this->isAllowedStatusTransition($currentStatus, $status)) {
                    return [
                        'status' => 'invalid_transition',
                        'current_status' => $currentStatus,
                        'target_status' => $status,
                        'allowed_statuses' => $this->allowedNextStatuses($currentStatus),
                    ];
                }

                if ($status === 'confirmed') {
                    SaleInvoice::query()
                        ->whereKey($existing->sale_invoice_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $this->assertStillDeliverable($existing);
                    $stockRows = $this->saleInvoiceService->issueDeliverNote($existing);
                }

                $updated = $this->repository->update($id, ['status' => $status]);

                if ($status === 'confirmed') {
                    $this->saleInvoiceService->postDeliverNoteCogsAccounting($updated, $stockRows ?? []);
                    $this->saleInvoiceService->postDeliverNoteCashbookTransaction($updated);
                    $this->syncInvoiceDeliveryMetrics($updated);
                    $this->saleInvoiceService->syncDeliveredStatusIfComplete((int) $existing->sale_invoice_id);
                }

                return ['status' => 'success', 'data' => $this->repository->find($id)];
            });
        } catch (Exception $e) {
            logger()->error('Error : Failed to update deliver note status: ' . $e->getMessage());
            throw $e;
        }
    }

    private function normalizePayload(array $attributes, ?DeliverNote $existing = null): array
    {
        $saleInvoiceId = (int) ($attributes['sale_invoice_id'] ?? $existing?->sale_invoice_id ?? 0);
        $saleInvoice = SaleInvoice::with([
            'branch',
            'inventory',
            'customer',
            'delivery.delivery_provider',
            'items.product',
            'items.uom',
        ])->find($saleInvoiceId);

        if (!$saleInvoice) {
            throw new \RuntimeException('Sale invoice not found.');
        }

        if (!in_array($saleInvoice->status, ['ordered', 'reserved'], true)) {
            throw new \RuntimeException('Deliver notes can only be created for ordered or reserved sale invoices.');
        }

        $itemsInput = $attributes['items'] ?? ($existing ? $existing->items->map(function ($item) {
            return [
                'sale_invoice_item_id' => $item->sale_invoice_item_id,
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'quantity' => $item->quantity,
                'remark' => $item->remark,
            ];
        })->toArray() : []);

        if (empty($itemsInput)) {
            throw new \RuntimeException('At least one deliver note item is required.');
        }

        $invoiceItems = $saleInvoice->items->keyBy('id');
        $normalizedItems = [];
        foreach ($itemsInput as $item) {
            $saleInvoiceItemId = (int) ($item['sale_invoice_item_id'] ?? 0);
            $invoiceItem = $invoiceItems->get($saleInvoiceItemId);
            if (!$invoiceItem) {
                throw new \RuntimeException('Deliver note item must belong to the selected sale invoice.');
            }

            $productId = (int) ($item['product_id'] ?? $invoiceItem->product_id);
            if (isset($item['product_id']) && $productId !== (int) $invoiceItem->product_id) {
                throw new \RuntimeException('Deliver note product must match the selected sale invoice item.');
            }

            $quantity = (float) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new \RuntimeException('Deliver note item quantity must be greater than zero.');
            }

            $remainingQty = (float) ($invoiceItem->remaining_delivery_qty ?? max((float) ($invoiceItem->order_qty ?? $invoiceItem->quantity ?? 0) - (float) ($invoiceItem->previously_deliver_qty ?? 0), 0));

            if ($quantity > $remainingQty + 0.00001) {
                throw new \RuntimeException('Deliver note item quantity exceeds the remaining delivery quantity.');
            }

            $unitPrice = (float) ($invoiceItem->unit_price ?? 0);
            $totalPrice = round($quantity * $unitPrice, 2);

            $normalizedItems[] = [
                'sale_invoice_item_id' => $saleInvoiceItemId,
                'product_id' => $productId,
                'uom_id' => (int) $invoiceItem->uom_id,
                'quantity' => round($quantity, 2),
                'unit_price' => round($unitPrice, 2),
                'total_price' => round($totalPrice, 2),
                'remark' => $item['remark'] ?? null,
            ];
        }

        $header = [
            'sale_invoice_id' => $saleInvoice->id,
            'branch_id' => (int) $saleInvoice->branch_id,
            'source_inventory_id' => (int) ($attributes['source_inventory_id']
                ?? $existing?->source_inventory_id
                ?? $saleInvoice->inventory_id),
            'customer_id' => (int) $saleInvoice->customer_id,
            'currency_id' => (int) $saleInvoice->currency_id,
            'delivery_date' => $attributes['delivery_date']
                ?? $existing?->delivery_date
                ?? now(),
            'delivery_provider_id' => $attributes['delivery_provider_id']
                ?? $existing?->delivery_provider_id
                ?? $saleInvoice->delivery?->delivery_provider_id,
            'receiver_name' => $attributes['receiver_name']
                ?? $existing?->receiver_name
                ?? $saleInvoice->delivery?->receiver_name,
            'receiver_phone' => $attributes['receiver_phone']
                ?? $existing?->receiver_phone
                ?? $saleInvoice->delivery?->receiver_phone,
            'receiver_address' => $attributes['receiver_address']
                ?? $existing?->receiver_address
                ?? $saleInvoice->delivery?->receiver_address,
            'delivery_note' => $attributes['delivery_note'] ?? $existing?->delivery_note ?? null,
        ];

        return [
            'header' => $header,
            'items' => $normalizedItems,
        ];
    }

    private function getConfirmedQuantitiesMap(int $saleInvoiceId, ?int $excludeDeliverNoteId = null): array
    {
        $query = DB::table('deliver_note_items as dni')
            ->join('deliver_notes as dn', 'dn.id', '=', 'dni.deliver_note_id')
            ->where('dn.sale_invoice_id', $saleInvoiceId)
            ->where('dn.status', 'confirmed')
            ->selectRaw('dni.sale_invoice_item_id, SUM(dni.quantity) as delivered_quantity')
            ->groupBy('dni.sale_invoice_item_id');

        if ($excludeDeliverNoteId) {
            $query->where('dn.id', '!=', $excludeDeliverNoteId);
        }

        return $query->pluck('delivered_quantity', 'sale_invoice_item_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function syncInvoiceDeliveryMetrics(DeliverNote $deliverNote): void
    {
        $invoice = SaleInvoice::with('items')->find($deliverNote->sale_invoice_id);
        if (!$invoice) {
            return;
        }

        $deliveredMap = $this->getConfirmedQuantitiesMap((int) $invoice->id);

        foreach ($invoice->items as $invoiceItem) {
            $deliveredQty = (float) ($deliveredMap[(int) $invoiceItem->id] ?? 0);
            $orderQty = (float) ($invoiceItem->order_qty ?? $invoiceItem->quantity ?? 0);
            $invoiceItem->update([
                'previously_deliver_qty' => round($deliveredQty, 2),
                'remaining_delivery_qty' => round(max($orderQty - $deliveredQty, 0), 2),
                'reserved_qty' => $invoice->status === 'reserved'
                    ? round(max($orderQty - $deliveredQty, 0), 2)
                    : 0,
            ]);
        }
    }

    private function assertStillDeliverable(DeliverNote $deliverNote): void
    {
        $invoice = SaleInvoice::with('items')->find($deliverNote->sale_invoice_id);
        if (!$invoice) {
            throw new \RuntimeException('Sale invoice not found.');
        }

        $noteItems = $deliverNote->items->groupBy('sale_invoice_item_id');
        foreach ($noteItems as $saleInvoiceItemId => $items) {
            $invoiceItem = $invoice->items->firstWhere('id', (int) $saleInvoiceItemId);
            if (!$invoiceItem) {
                throw new \RuntimeException('Deliver note item must belong to the selected sale invoice.');
            }

            $requestedQty = round((float) $items->sum('quantity'), 2);
            $remainingQty = (float) ($invoiceItem->remaining_delivery_qty ?? max((float) ($invoiceItem->order_qty ?? $invoiceItem->quantity ?? 0) - (float) ($invoiceItem->previously_deliver_qty ?? 0), 0));

            if ($requestedQty > $remainingQty + 0.00001) {
                throw new \RuntimeException('Deliver note item quantity exceeds the remaining delivery quantity.');
            }
        }
    }

    private function isAllowedStatusTransition(string $current, string $next): bool
    {
        return in_array($next, $this->allowedNextStatuses($current), true);
    }

    private function allowedNextStatuses(string $current): array
    {
        return [
            'draft' => ['pending', 'cancelled'],
            'pending' => ['draft', 'confirmed', 'rejected', 'cancelled'],
            'rejected' => ['draft', 'cancelled'],
            'confirmed' => [],
            'cancelled' => [],
        ][$current] ?? [];
    }

    private function generateDeliverNoteNumber(): string
    {
        $prefix = 'DN-' . now()->format('Ymd') . '-';
        $lastNumber = DeliverNote::where('deliver_note_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('deliver_note_no');

        $nextSequence = 1;
        if ($lastNumber) {
            $nextSequence = ((int) substr($lastNumber, strrpos($lastNumber, '-') + 1)) + 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
