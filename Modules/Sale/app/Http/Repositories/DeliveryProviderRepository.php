<?php

namespace Modules\Sale\app\Http\Repositories;

use Modules\Sale\app\Models\DeliveryProvider;

class DeliveryProviderRepository extends BaseRepo
{
    public function __construct(DeliveryProvider $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);

        if ($data) {
            $data->load(['created_by', 'updated_by']);
        }

        return $data;
    }

    public function toggleActive(DeliveryProvider $delivery_provider)
    {
        $delivery_provider->updated_by = auth()->user()->id;

        if ($delivery_provider->status === 'active') {
            $delivery_provider->status = 'inactive';
        } else {
            $delivery_provider->status = 'active';
        }

        $delivery_provider->save();
    }
}
