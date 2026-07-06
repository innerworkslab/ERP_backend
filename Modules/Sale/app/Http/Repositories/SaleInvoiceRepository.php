<?php

namespace Modules\Sale\app\Http\Repositories;

use Modules\Sale\app\Models\SaleInvoice;

class SaleInvoiceRepository extends BaseRepo
{
    public function __construct(SaleInvoice $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        return $this->model->with([
            'branch',
            'inventory',
            'customer',
            'currency',
            'selling_price_group',
            'sell_tax',
            'cashbook',
            'items.product',
            'items.uom',
            'delivery.delivery_provider',
        ])->find($id);
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
    }

    public function getDataWithPagination(
        $perPage = 10,
        $page = 1,
        $orderBy = 'invoice_date',
        $searches = null,
        $conditions = [],
        $orConditions = [],
        $with = [],
        $whereHas = null,
        $status = null
    ) {
        $query = $this->model->query();

        if (count($with) > 0) {
            $query->with($with);
        } else {
            $query->with([
                'branch',
                'inventory',
                'customer',
                'currency',
                'selling_price_group',
                'sell_tax',
                'cashbook',
                'delivery.delivery_provider',
            ]);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($conditions['invoice_date_from'])) {
            $query->whereDate('invoice_date', '>=', $conditions['invoice_date_from']);
        }

        if (!empty($conditions['invoice_date_to'])) {
            $query->whereDate('invoice_date', '<=', $conditions['invoice_date_to']);
        }

        foreach ([
            'branch_id',
            'inventory_id',
            'customer_id',
            'currency_id',
            'selling_price_group_id',
            'payment_status',
            'cashbook_id',
        ] as $column) {
            if (array_key_exists($column, $conditions) && $conditions[$column] !== null && $conditions[$column] !== '') {
                $query->where($column, $conditions[$column]);
            }
        }

        if (array_key_exists('delivery_provider_id', $conditions) && $conditions['delivery_provider_id'] !== null && $conditions['delivery_provider_id'] !== '') {
            $query->whereHas('delivery', function ($q) use ($conditions) {
                $q->where('delivery_provider_id', $conditions['delivery_provider_id']);
            });
        }

        $search = null;
        if (is_array($searches) && !empty($searches)) {
            $search = array_values($searches)[0];
        } elseif (is_string($searches) && $searches !== '') {
            $search = $searches;
        }

        if (!empty($search)) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->orWhere('invoice_number', 'like', $like)
                    ->orWhere('remarks', 'like', $like)
                    ->orWhereHas('customer', function ($customerQuery) use ($like) {
                        $customerQuery->where('name', 'like', $like)
                            ->orWhere('phone_number', 'like', $like);
                    })
                    ->orWhereHas('items', function ($itemQuery) use ($like) {
                        $itemQuery->where('remarks', 'like', $like)
                            ->orWhereHas('product', function ($productQuery) use ($like) {
                                $productQuery->where('name', 'like', $like)
                                    ->orWhere('sku', 'like', $like);
                            });
                    })
                    ->orWhereHas('delivery.delivery_provider', function ($deliveryQuery) use ($like) {
                        $deliveryQuery->where('name', 'like', $like);
                    });
            });
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }

        $countQuery = clone $query;
        $totalCount = $countQuery->count();

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        $offset = $perPage * ($page - 1);
        if ($offset > 0) {
            $query->offset($offset);
        }

        $results = $query->limit($perPage)->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($totalCount / max((int) $perPage, 1)),
            ],
        ];
    }

    public function createWithRelations(array $invoiceData, array $items, ?array $deliveryData = null)
    {
        $invoice = $this->model->create($invoiceData);
        $invoice->items()->createMany($items);

        if ($deliveryData) {
            $invoice->delivery()->create($deliveryData);
        }

        return $this->find($invoice->id);
    }

    public function updateWithRelations(int $id, array $invoiceData, array $items, ?array $deliveryData = null)
    {
        $invoice = $this->model->find($id);
        if (!$invoice) {
            return null;
        }

        $invoice->update($invoiceData);
        $invoice->items()->delete();
        $invoice->items()->createMany($items);

        if ($deliveryData !== null) {
            $invoice->delivery()->delete();
            $invoice->delivery()->create($deliveryData);
        }

        return $this->find($id);
    }

    public function deleteWithRelations(int $id): bool
    {
        $invoice = $this->model->find($id);
        if (!$invoice) {
            return false;
        }

        $invoice->delivery()->delete();
        $invoice->items()->delete();
        $invoice->delete();

        return true;
    }
}
