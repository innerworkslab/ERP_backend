<?php

namespace Modules\Stakeholder\app\Http\Services\SupplierType;

use Illuminate\Support\Collection;
use Modules\Stakeholder\app\Http\Repositories\SupplierType\SupplierTypeRepository;
use Modules\Stakeholder\app\Models\SupplierType;

class SupplierTypeService
{
    public function __construct(private SupplierTypeRepository $supplierTypeRepository)
    {
    }

    public function list(): Collection
    {
        return SupplierType::query()->latest()->get();
    }

    public function create(array $attributes): SupplierType
    {
        return $this->supplierTypeRepository->create($attributes);
    }
}
