<?php

namespace Modules\Stakeholder\app\Http\Services\Supplier;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Stakeholder\app\Models\Supplier;
use Modules\Stakeholder\app\Http\Repositories\Supplier\SupplierRepository;

class SupplierService
{
    private const PREFIX = 'SUP';
    private const RUNNING_NUMBER_LENGTH = 6;

    public function __construct(private SupplierRepository $supplierRepository)
    {
    }

    public function list(array $filters): array
    {
        $query = Supplier::query()->latest();

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
            $branchId = (int) ($attributes['branch_id'] ?? 0);
            $runningNumber = $this->getNextRunningNumber($branchId);

            $attributes['code'] = 'TMP-SUP-' . uniqid();

            $supplier = $this->supplierRepository->create($attributes);

            $supplier->update([
                'code' => $this->generateCode($supplier->id, $branchId, $runningNumber),
            ]);

            return $supplier->fresh();
        });
    }

    public function findOrFail(int $id): Supplier
    {
        $model = $this->supplierRepository->find($id);

        if (!$model) {
            throw new ModelNotFoundException('Supplier not found.');
        }

        return $model;
    }

    public function update(int $id, array $attributes): ?Supplier
    {
        if (!$this->supplierRepository->find($id)) {
            return null;
        }

        unset($attributes['code']);
        return $this->supplierRepository->update($id, $attributes);
    }

    public function delete(int $id): bool
    {
        return $this->supplierRepository->delete($id);
    }

    private function generateCode(int $id, int $branchId, int $runningNumber): string
    {
        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch ID is required for supplier code generation.'],
            ]);
        }

        return $id . '-' . $branchId . '-' . self::PREFIX . '-' . str_pad((string) $runningNumber, self::RUNNING_NUMBER_LENGTH, '0', STR_PAD_LEFT);
    }

    private function getNextRunningNumber(int $branchId): int
    {
        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch ID is required for supplier code generation.'],
            ]);
        }

        $baseCodePattern = '/^\d+-' . preg_quote((string) $branchId, '/') . '-' . self::PREFIX . '-(\d+)$/';
        $latestCode = (string) Supplier::query()
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