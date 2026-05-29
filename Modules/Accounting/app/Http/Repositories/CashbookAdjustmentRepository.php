<?php

namespace Modules\Accounting\app\Http\Repositories;

use RuntimeException;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Accounting\app\Models\CashbookAdjustment;
use Modules\Accounting\app\Models\JournalEntry;
use Modules\Accounting\app\Models\JournalPosting;

class CashbookAdjustmentRepository extends BaseRepo
{
    public function __construct(CashbookAdjustment $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $query = $this->model->query()
            ->with(['cashbook.branch', 'cashbook.currency', 'creator', 'approver', 'branch'])
            ->whereKey($id);

        $this->applyBranchScope($query);

        return $query->first();
    }

    public function create(array $data)
    {
        $user = auth()->user();
        $cashbook = Cashbook::query()->find($data['cashbook_id']);
        $this->assertCashbookAllowed($cashbook);

        $data['branch_id'] = (int) $cashbook->branch_id;
        $data['created_by'] = (int) $user->id;
        $data['status'] = 'pending';
        $data['reference_no'] = $this->generateReferenceNo();

        $adjustment = $this->model->create($data);
        return $this->find($adjustment->id);
    }

    public function update($id, array $data)
    {
        $adjustment = $this->find($id);
        if (!$adjustment) {
            return null;
        }

        if ($adjustment->status !== 'pending') {
            throw new RuntimeException('Only pending cashbook adjustment can be updated.');
        }

        $cashbook = Cashbook::query()->find($data['cashbook_id']);
        $this->assertCashbookAllowed($cashbook);

        $data['branch_id'] = (int) $cashbook->branch_id;
        $adjustment->update($data);

        return $this->find($adjustment->id);
    }

    public function approve(int $id)
    {
        $adjustment = $this->model->query()->lockForUpdate()->find($id);
        if (!$adjustment) {
            return null;
        }

        $this->assertAdjustmentAllowed($adjustment);

        if ($adjustment->status === 'approved') {
            return $this->find($adjustment->id);
        }

        if ($adjustment->status === 'rejected') {
            return $this->find($adjustment->id);
        }

        $cashbook = Cashbook::query()->lockForUpdate()->find($adjustment->cashbook_id);
        $this->assertCashbookAllowed($cashbook);

        $amount = (float) $adjustment->amount;
        $beforeBalance = (float) $cashbook->current_balance;

        if ($adjustment->type === 'decrease' && $beforeBalance < $amount) {
            throw new RuntimeException('Insufficient cashbook balance for adjustment.');
        }

        $afterBalance = $adjustment->type === 'increase'
            ? $beforeBalance + $amount
            : $beforeBalance - $amount;

        $cashbook->update([
            'current_balance' => $afterBalance,
            'updated_by' => auth()->id(),
        ]);

        $entry = JournalEntry::query()->create([
            'journal_datetime' => now(),
            'source_type' => CashbookAdjustment::class,
            'source_id' => $adjustment->id,
            'description' => 'Cashbook adjustment ' . $adjustment->reference_no,
        ]);

        $postingType = $adjustment->type === 'increase' ? 'debit' : 'credit';
        
        JournalPosting::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => (int) $cashbook->account_id,
            'type' => $postingType,
            'currency_id' => (int) $cashbook->currency_id,
            'amount' => $amount,
            'base_currency_amount' => $amount,
        ]);

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->find($adjustment->id);
    }

    public function reject(int $id)
    {
        $adjustment = $this->model->query()->lockForUpdate()->find($id);
        if (!$adjustment) {
            return null;
        }

        $this->assertAdjustmentAllowed($adjustment);

        if ($adjustment->status === 'rejected') {
            return $this->find($adjustment->id);
        }

        if ($adjustment->status === 'approved') {
            return $this->find($adjustment->id);
        }

        $adjustment->update([
            'status' => 'rejected'
        ]);

        return $this->find($adjustment->id);
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

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($searches) {
            $query->where(function ($searchQuery) use ($searches) {
                foreach ($searches as $key => $value) {
                    $searchQuery->orWhere($key, 'LIKE', '%' . $value . '%');
                }
            });
        }

        foreach ($conditions as $key => $condition) {
            if ($key === 'from_date') {
                $query->whereDate('created_at', '>=', $condition);
                continue;
            }

            if ($key === 'to_date') {
                $query->whereDate('created_at', '<=', $condition);
                continue;
            }

            $query->where($key, $condition);
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }

        $offset = $perPage * ($page - 1);
        $totalCount = $query->count();

        if ($offset > 0) {
            $query->offset($offset);
        }

        $results = $query->limit($perPage)->latest()->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($totalCount / $perPage),
            ],
        ];
    }

    private function generateReferenceNo(): string
    {
        $prefix = 'CBAD-' . now()->format('Ymd') . '-';

        $latest = $this->model->where('reference_no', 'LIKE', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return $prefix . '000001';
        }

        $lastSequence = (int) substr($latest->reference_no, strrpos($latest->reference_no, '-') + 1);
        return $prefix . str_pad((string) ($lastSequence + 1), 6, '0', STR_PAD_LEFT);
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

    private function isSuperAdmin(): bool
    {
        $roleName = strtolower(trim((string) (auth()->user()?->role?->name ?? '')));
        return $roleName === 'super admin';
    }

    private function assertCashbookAllowed(?Cashbook $cashbook): void
    {
        if (!$cashbook) {
            throw new RuntimeException('Cashbook not found.');
        }

        if ($this->isSuperAdmin()) {
            return;
        }

        $branchIds = auth()->user()?->branches?->pluck('id')->toArray() ?? [];
        if (!in_array((int) $cashbook->branch_id, $branchIds, true)) {
            throw new RuntimeException('You are not allowed to access this branch cashbook.');
        }
    }

    private function assertAdjustmentAllowed(CashbookAdjustment $adjustment): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $branchIds = auth()->user()?->branches?->pluck('id')->toArray() ?? [];
        if (!in_array((int) $adjustment->branch_id, $branchIds, true)) {
            throw new RuntimeException('You are not allowed to access this branch adjustment.');
        }
    }
}
