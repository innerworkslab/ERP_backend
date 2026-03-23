<?php

namespace Modules\Stakeholder\app\Http\Services\Customer;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Stakeholder\app\Models\Customer;
use Modules\Stakeholder\app\Http\Repositories\Customer\CustomerRepository;

class CustomerService
{
    private const PREFIX = 'CUS';
    private const RUNNING_NUMBER_LENGTH = 6;
    private const RELATIONS = ['customer_type', 'branch', 'created_by', 'updated_by'];

    public function __construct(private CustomerRepository $customerRepository)
    {
    }

    public function list(array $filters): array
    {
        $query = Customer::query()->with(self::RELATIONS)->latest();

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
        return DB::transaction(function () use ($attributes) {
            $userId = auth()->id();
            $branchId = (int) ($attributes['branch_id'] ?? 0);
            $runningNumber = $this->getNextRunningNumber($branchId);

            unset($attributes['created_by'], $attributes['updated_by']);

            $attributes['code'] = 'TMP-CUS-' . uniqid();
            $attributes['created_by'] = $userId;

            $customer = $this->customerRepository->create($attributes);

            $customer->update([
                'code' => $this->generateCode($customer->id, $branchId, $runningNumber),
            ]);

            return $customer->fresh()->loadMissing(self::RELATIONS);
        });
    }

    public function findOrFail(int $id): Customer
    {
        $model = $this->customerRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Customer not found.');
        }

        return $model->loadMissing(self::RELATIONS);
    }

    public function update(int $id, array $attributes): ?Customer
    {
        if (!$this->customerRepository->find($id)) {
            return null;
        }

        unset($attributes['code'], $attributes['created_by'], $attributes['updated_by']);

        $attributes['updated_by'] = auth()->id();

        $customer = $this->customerRepository->update($id, $attributes);

        return $customer?->loadMissing(self::RELATIONS);
    }

    public function delete(int $id): bool
    {
        return $this->customerRepository->delete($id);
    }

    private function generateCode(int $id, int $branchId, int $runningNumber): string
    {
        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch ID is required for customer code generation.'],
            ]);
        }

        return $id . '-' . $branchId . '-' . self::PREFIX . '-' . str_pad((string) $runningNumber, self::RUNNING_NUMBER_LENGTH, '0', STR_PAD_LEFT);
    }

    private function getNextRunningNumber(int $branchId): int
    {
        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch ID is required for customer code generation.'],
            ]);
        }

        $baseCodePattern = '/^\d+-' . preg_quote((string) $branchId, '/') . '-' . self::PREFIX . '-(\d+)$/';
        $latestCode = (string) Customer::query()
            ->where('branch_id', $branchId)
            ->latest('id')
            ->lockForUpdate()
            ->value('code');

        if ($latestCode !== '' && preg_match($baseCodePattern, $latestCode, $matches)) {
            return ((int) $matches[1]) + 1;
        }

        return 1;
    }
}