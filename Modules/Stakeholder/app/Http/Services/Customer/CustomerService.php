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
    private const RELATIONS = ['customer_type', 'bank_accounts', 'state', 'city', 'branches', 'created_by', 'updated_by'];

    public function __construct(private CustomerRepository $customerRepository)
    {
    }

    public function list(array $filters): array
    {
        $query = $this->customerRepository->queryWithRelations()->latest();

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
            $branchIds = $this->resolveBranchIds($attributes);
            $branchId = $branchIds[0] ?? 0;
            $runningNumber = $this->getNextRunningNumber($branchId);
            $bankAccounts = $attributes['bank_accounts'] ?? null;

            unset($attributes['created_by'], $attributes['updated_by']);
            unset($attributes['bank_accounts'], $attributes['branch_id'], $attributes['branch_ids']);

            $attributes['code'] = 'TMP-CUS-' . uniqid();
            $attributes['created_by'] = $userId;

            $customer = $this->customerRepository->create($attributes);
            $this->customerRepository->syncBranches($customer, $branchIds);

            $customer->update([
                'code' => $this->generateCode($customer->id, $branchId, $runningNumber),
            ]);

            if (is_array($bankAccounts) && count($bankAccounts) > 0) {
                $customer->bank_accounts()->createMany($bankAccounts);
                $firstBankAccountId = $customer->bank_accounts()->value('id');
                if ($firstBankAccountId) {
                    $customer->update(['bank_account_id' => $firstBankAccountId]);
                }
            }

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
        $customerModel = $this->customerRepository->find($id);
        if (!$customerModel) {
            return null;
        }

        unset($attributes['code'], $attributes['created_by'], $attributes['updated_by']);
        $bankAccounts = $attributes['bank_accounts'] ?? null;
        $hasBranchPayload = array_key_exists('branch_ids', $attributes) || array_key_exists('branch_id', $attributes);
        $branchIds = $hasBranchPayload ? $this->resolveBranchIds($attributes, true) : [];
        unset($attributes['bank_accounts'], $attributes['branch_id'], $attributes['branch_ids']);

        $attributes['updated_by'] = auth()->id();

        $customer = $this->customerRepository->update($id, $attributes);
        if ($hasBranchPayload) {
            $this->customerRepository->syncBranches($customerModel, $branchIds);
        }

        if (is_array($bankAccounts)) {
            $customerModel->bank_accounts()->delete();
            if (count($bankAccounts) > 0) {
                $customerModel->bank_accounts()->createMany($bankAccounts);
                $firstBankAccountId = $customerModel->bank_accounts()->value('id');
                $customer?->update(['bank_account_id' => $firstBankAccountId]);
            } else {
                $customer?->update(['bank_account_id' => null]);
            }
        }

        return $customer?->loadMissing(self::RELATIONS);
    }

    public function delete(int $id): bool
    {
        return $this->customerRepository->delete($id);
    }

    public function toggleStatus(int $id): ?Customer
    {
        $customer = $this->customerRepository->find($id);
        if (!$customer) {
            return null;
        }

        $nextStatus = $customer->status === 'Active' ? 'Inactive' : 'Active';

        $customer = $this->customerRepository->update($id, [
            'status' => $nextStatus,
            'updated_by' => auth()->id(),
        ]);

        return $customer?->loadMissing(self::RELATIONS);
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

        return $this->customerRepository->getNextRunningNumberByBranch($branchId, self::PREFIX);
    }

    private function resolveBranchIds(array $attributes, bool $allowEmpty = false): array
    {
        $branchIds = $attributes['branch_ids'] ?? null;

        if (is_array($branchIds)) {
            return array_values(array_unique(array_map('intval', $branchIds)));
        }

        if (!empty($attributes['branch_id'])) {
            return [(int) $attributes['branch_id']];
        }

        if ($allowEmpty) {
            return [];
        }

        throw ValidationException::withMessages([
            'branch_ids' => ['At least one branch is required for customer.'],
        ]);
    }
}
