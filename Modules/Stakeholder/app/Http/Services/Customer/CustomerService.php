<?php

namespace Modules\Stakeholder\app\Http\Services\Customer;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Stakeholder\app\Models\Customer;
use Modules\Stakeholder\app\Http\Repositories\Customer\CustomerRepository;

class CustomerService
{
    public function __construct(private CustomerRepository $customerRepository)
    {
    }

    public function list(array $filters): array
    {
        $query = Customer::query()->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 10);
        $page = (int) ($filters['page'] ?? 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ];
    }

    public function create(array $attributes): Customer
    {
        $attributes['code'] = $this->generateCode();

        return $this->customerRepository->create($attributes);
    }

    public function findOrFail(int $id): Customer
    {
        $model = $this->customerRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Customer not found.');
        }

        return $model;
    }

    public function update(int $id, array $attributes): ?Customer
    {
        if (!$this->customerRepository->find($id)) {
            return null;
        }

        unset($attributes['code']);
        return $this->customerRepository->update($id, $attributes);
    }

    public function delete(int $id): bool
    {
        return $this->customerRepository->delete($id);
    }

    private function generateCode(): string
    {
        $prefix = 'CUS';
        $length = 6;
        $latestCode = (string) Customer::query()->latest('id')->value('code');
        $runningNumber = 1;

        if (preg_match('/\d+$/', $latestCode, $numberMatches)) {
            $runningNumber = ((int) $numberMatches[0]) + 1;
        }

        do {
            $code = $prefix . str_pad((string) $runningNumber, $length, '0', STR_PAD_LEFT);
            $runningNumber++;
        } while (Customer::query()->where('code', $code)->exists());

        return $code;
    }
}