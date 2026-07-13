<?php

namespace Modules\Sale\app\Http\Repositories;

use Modules\Sale\app\Models\DeliverNote;

class DeliverNoteRepository extends BaseRepo
{
    public function __construct(DeliverNote $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        return $this->model->with([
            'saleInvoice.branch',
            'saleInvoice.inventory',
            'saleInvoice.customer',
            'saleInvoice.currency',
            'branch',
            'sourceInventory',
            'customer',
            'currency',
            'deliveryProvider',
            'items.saleInvoiceItem.product',
            'items.product',
            'items.uom',
        ])->find($id);
    }

    public function getDataWithPagination(
        $perPage = 10,
        $page = 1,
        $orderBy = 'created_at',
        $searches = null,
        $conditions = [],
        $orConditions = [],
        $with = [],
        $whereHas = null,
        $status = null
    ) {
        $query = $this->model->query()->with($with ?: [
            'saleInvoice.branch',
            'saleInvoice.inventory',
            'saleInvoice.customer',
            'saleInvoice.currency',
            'branch',
            'sourceInventory',
            'customer',
            'currency',
            'deliveryProvider',
            'items.saleInvoiceItem.product',
            'items.product',
            'items.uom',
        ]);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        foreach ([
            'sale_invoice_id',
            'branch_id',
            'source_inventory_id',
            'customer_id',
            'delivery_provider_id',
        ] as $column) {
            if (array_key_exists($column, $conditions) && $conditions[$column] !== null && $conditions[$column] !== '') {
                $query->where($column, $conditions[$column]);
            }
        }

        if (!empty($conditions['delivery_date_from'])) {
            $query->whereDate('delivery_date', '>=', $conditions['delivery_date_from']);
        }

        if (!empty($conditions['delivery_date_to'])) {
            $query->whereDate('delivery_date', '<=', $conditions['delivery_date_to']);
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
                $q->orWhere('deliver_note_no', 'like', $like)
                    ->orWhere('receiver_name', 'like', $like)
                    ->orWhere('receiver_phone', 'like', $like)
                    ->orWhere('receiver_address', 'like', $like)
                    ->orWhere('delivery_note', 'like', $like)
                    ->orWhereHas('saleInvoice', function ($invoiceQuery) use ($like) {
                        $invoiceQuery->where('invoice_number', 'like', $like);
                    })
                    ->orWhereHas('customer', function ($customerQuery) use ($like) {
                        $customerQuery->where('name', 'like', $like)
                            ->orWhere('phone_number', 'like', $like);
                    })
                    ->orWhereHas('deliveryProvider', function ($providerQuery) use ($like) {
                        $providerQuery->where('name', 'like', $like);
                    })
                    ->orWhereHas('items.product', function ($productQuery) use ($like) {
                        $productQuery->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like);
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

    public function createWithRelations(array $header, array $items)
    {
        $note = $this->model->create($header);
        $note->items()->createMany($items);

        return $this->find($note->id);
    }

    public function updateWithRelations(int $id, array $header, array $items)
    {
        $note = $this->model->find($id);
        if (!$note) {
            return null;
        }

        $note->update($header);
        $note->items()->delete();
        $note->items()->createMany($items);

        return $this->find($id);
    }
}
