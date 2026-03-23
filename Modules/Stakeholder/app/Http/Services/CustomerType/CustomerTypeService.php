<?php

namespace Modules\Stakeholder\app\Http\Services\CustomerType;

use Illuminate\Support\Collection;
use Modules\Stakeholder\app\Models\CustomerType;
use Modules\Stakeholder\app\Http\Repositories\CustomerType\CustomerTypeRepository;

class CustomerTypeService
{
    public function __construct(private CustomerTypeRepository $customerTypeRepository)
    {
    }

    public function list(): Collection
    {
        return CustomerType::query()->latest()->get();
    }

    public function create(array $attributes): CustomerType
    {
        return $this->customerTypeRepository->create($attributes);
    }
}
