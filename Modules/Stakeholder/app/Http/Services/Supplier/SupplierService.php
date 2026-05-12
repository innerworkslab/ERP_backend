<?php

namespace Modules\Stakeholder\app\Http\Services\Supplier;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Stakeholder\app\Models\Supplier;
use Modules\Stakeholder\app\Http\Repositories\Supplier\SupplierRepository;

class SupplierService
{
    private const CODE_PREFIX = 'SUP-';
    private const CODE_NUMBER_LENGTH = 4;
    private const RELATIONS = ['supplier_type', 'bank_accounts', 'state', 'city', 'created_by', 'updated_by'];

    public function __construct(private SupplierRepository $supplierRepository)
    {
    }

    public function list(array $filters): array
    {
        $query = Supplier::query()->with(self::RELATIONS)->latest();

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

    public function create(array $attributes): Supplier
    {
        return DB::transaction(function () use ($attributes) {
            $userId = auth()->id();
            $bankAccounts = $attributes['bank_accounts'] ?? [];

            unset($attributes['created_by'], $attributes['updated_by']);
            unset($attributes['bank_accounts']);

            $attributes['code'] = $this->generateNextSupplierCode();
            $attributes['created_by'] = $userId;
            $attributes['bank_account_id'] = $attributes['bank_account_id'] ?? 0;

            $supplier = $this->supplierRepository->create($attributes);

            if (count($bankAccounts) > 0) {
                $supplier->bank_accounts()->createMany($bankAccounts);
                $firstBankAccountId = $supplier->bank_accounts()->value('id');
                if ($firstBankAccountId) {
                    $supplier->update(['bank_account_id' => $firstBankAccountId]);
                }
            }

            return $supplier->fresh()->loadMissing(self::RELATIONS);
        });
    }

    public function findOrFail(int $id): Supplier
    {
        $model = $this->supplierRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Supplier not found.');
        }

        return $model->loadMissing(self::RELATIONS);
    }

    public function update(int $id, array $attributes): ?Supplier
    {
        $supplierModel = $this->supplierRepository->find($id);
        if (!$supplierModel) {
            return null;
        }

        unset($attributes['code'], $attributes['created_by'], $attributes['updated_by']);
        $bankAccounts = $attributes['bank_accounts'] ?? null;
        unset($attributes['bank_accounts']);

        $attributes['updated_by'] = auth()->id();

        $supplier = $this->supplierRepository->update($id, $attributes);
        if (is_array($bankAccounts) && count($bankAccounts) > 0) {
            $supplierModel->bank_accounts()->delete();
            $supplierModel->bank_accounts()->createMany($bankAccounts);
            $firstBankAccountId = $supplierModel->bank_accounts()->value('id');
            if ($firstBankAccountId) {
                $supplier?->update(['bank_account_id' => $firstBankAccountId]);
            }
        }

        return $supplier?->loadMissing(self::RELATIONS);
    }

    public function delete(int $id): bool
    {
        return $this->supplierRepository->delete($id);
    }

    public function toggleStatus(int $id): ?Supplier
    {
        $supplier = $this->supplierRepository->find($id);
        if (!$supplier) {
            return null;
        }

        $nextStatus = $supplier->status === 'Active' ? 'Inactive' : 'Active';

        $supplier = $this->supplierRepository->update($id, [
            'status' => $nextStatus,
            'updated_by' => auth()->id(),
        ]);

        return $supplier?->loadMissing(self::RELATIONS);
    }

    private function generateNextSupplierCode(): string
    {
        $latestCode = (string) Supplier::query()
            ->where('code', 'like', self::CODE_PREFIX . '%')
            ->latest('id')
            ->lockForUpdate()
            ->value('code');

        $nextNumber = 1;
        if ($latestCode !== '' && preg_match('/^SUP-(\d+)$/', $latestCode, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return self::CODE_PREFIX . str_pad((string) $nextNumber, self::CODE_NUMBER_LENGTH, '0', STR_PAD_LEFT);
    }
}
