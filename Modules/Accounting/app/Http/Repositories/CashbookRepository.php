<?php

namespace Modules\Accounting\app\Http\Repositories;

use RuntimeException;
use Modules\Accounting\app\Http\Repositories\BaseRepo;
use Modules\Accounting\app\Models\Account;
use Modules\Accounting\app\Models\Cashbook;


class CashbookRepository extends BaseRepo
{
    protected $account_repository;

    public function __construct(Cashbook $model, AccountRepository $account_repository)
    {
        parent::__construct($model);
        $this->account_repository = $account_repository;
    }

    public function toggleActive(Cashbook $cashbook)
    {
        $cashbook->updated_by = auth()->user()->id;
        if ($cashbook->status == 'active') {
            $cashbook->status = 'inactive';
        } else {
            $cashbook->status = 'active';
        }
        $cashbook->save();
    }

    public function find($id)
    {
        $query = $this->model->query()->whereKey($id);
        $this->applyBranchScope($query);
        $data = $query->first();
        if ($data) {
            $data->load(['created_by', 'updated_by', 'branch', 'account', 'currency']);
        }
        return $data;
    }

    public function create($data)
    {
        $this->assertBranchAllowed((int) $data['branch_id']);

        $parent_account = Account::where('code', '2-1001')->first();
        $code = $this->account_repository->generateAccountCode($parent_account->id);

        $account = $this->account_repository->create([
            'parent_account_id' => $parent_account->id,
            'code' => $code,
            'name' => $data['name'],
            'type' => 'Cash',
            'division' => 'SOFP',
            'description' => 'Auto generated from cashbook',
            'is_active' => true,
        ]);
        $data['account_id'] = $account->id;

        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $cashbook = $this->find($id);
        if (!$cashbook) {
            return null;
        }

        if (array_key_exists('branch_id', $data)) {
            $this->assertBranchAllowed((int) $data['branch_id']);
        }

        $cashbook->update($data);
        return $cashbook;
    }

    public function getDataWithPagination($perPage = 10, $page = 1, $orderBy = 'created_at', $searches = null, $conditions = [], $orConditions = [], $with = [], $whereHas = null, $status = null)
    {
        $query = $this->model->query();
        $this->applyBranchScope($query);

        if (count($with) > 0) {
            $query->with($with);
        }

        if ($whereHas && is_array($whereHas)) {
            foreach ($whereHas as $relation => $constraint) {
                $query->whereHas($relation, $constraint);
            }
        }

        $offset = $perPage * ($page - 1);

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        $query->limit($perPage);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($searches) {
            $query->where(function ($q) use ($searches) {
                foreach ($searches as $key => $value) {
                    $q->orWhere($key, 'LIKE', "%$value%");
                }
            });
        }

        foreach ($conditions as $key => $condition) {
            $query->where($key, $condition);
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }

        $totalCount = $query->count();

        if ($offset > 0) {
            $query->offset($offset);
        }

        $results = $query->latest()->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $perPage),
            ],
        ];
    }

    private function applyBranchScope($query): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $branchIds = auth()->user()?->branches?->pluck('id')->toArray() ?? [];
        if (empty($branchIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('branch_id', $branchIds);
    }

    private function assertBranchAllowed(int $branchId): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $branchIds = auth()->user()?->branches?->pluck('id')->toArray() ?? [];
        if (!in_array($branchId, $branchIds, true)) {
            throw new RuntimeException('You are not allowed to access this branch cashbook.');
        }
    }

    private function isSuperAdmin(): bool
    {
        $roleName = strtolower(trim((string) (auth()->user()?->role?->name ?? '')));
        return $roleName === 'super admin';
    }
}
