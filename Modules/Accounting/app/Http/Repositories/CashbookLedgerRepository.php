<?php

namespace Modules\Accounting\app\Http\Repositories;

use Carbon\Carbon;
use Modules\Accounting\app\Models\CashbookLedger;
use Modules\Accounting\app\Models\Cashbook;

class CashbookLedgerRepository extends BaseRepo
{
    public function __construct(CashbookLedger $model)
    {
        parent::__construct($model);
    }


    public function getDataWithPagination($perPage = 10, $page = 1, $orderBy = 'created_at', $searches = null, $conditions = [], $orConditions = [], $with = [], $whereHas = null, $status = null)
    {
        $query = $this->model->query();

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

        if ($searches) {
            $query->where(function ($q) use ($searches) {
                foreach ($searches as $key => $value) {
                    $q->orWhere($key, 'LIKE', "%$value%");
                }
            });
        }

        if (!empty($conditions['from_date'])) {
            $query->whereDate('transaction_datetime', '>=', $conditions['from_date']);
            unset($conditions['from_date']);
        }

        if (!empty($conditions['to_date'])) {
            $query->whereDate('transaction_datetime', '<=', $conditions['to_date']);
            unset($conditions['to_date']);
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

    public function getDailyStatement(array $filters): array
    {
        $cashbookId = (int) $filters['cashbook_id'];
        $selectedDate = Carbon::parse($filters['date'] ?? now()->toDateString());
        $fromDate = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->startOfDay()
            : $selectedDate->copy()->startOfDay();
        $toDate = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->endOfDay()
            : $selectedDate->copy()->endOfDay();

        $cashbook = Cashbook::find($cashbookId);

        $openingLedger = $this->model->query()
            ->where('cashbook_id', $cashbookId)
            ->where('transaction_datetime', '<', $fromDate)
            ->orderByDesc('transaction_datetime')
            ->orderByDesc('id')
            ->first();

        $openingBalance = $openingLedger
            ? (float) $openingLedger->after_balance
            : (float) ($cashbook?->current_balance ?? 0);

        $query = $this->model->query()
            ->with(['cashbook', 'transaction'])
            ->where('cashbook_id', $cashbookId)
            ->whereBetween('transaction_datetime', [$fromDate, $toDate]);

        if (!empty($filters['cashbook_transaction_id'])) {
            $query->where('cashbook_transaction_id', $filters['cashbook_transaction_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('remark', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $ledgers = $query
            ->orderBy('transaction_datetime')
            ->orderBy('id')
            ->get();

        $runningBalance = $openingBalance;
        $rows = [];

        foreach ($ledgers as $ledger) {
            $amount = (float) $ledger->amount;

            if ($ledger->transaction_type === 'in') {
                $runningBalance += $amount;
            } else {
                $runningBalance -= $amount;
            }

            $rows[] = [
                'row_type' => 'transaction',
                'id' => $ledger->id,
                'cashbook_id' => $ledger->cashbook_id,
                'cashbook_transaction_id' => $ledger->cashbook_transaction_id,
                'cashbook' => $ledger->cashbook ? [
                    'id' => $ledger->cashbook->id,
                    'name' => $ledger->cashbook->name,
                    'branch_id' => $ledger->cashbook->branch_id,
                    'currency_id' => $ledger->cashbook->currency_id,
                    'current_balance' => $ledger->cashbook->current_balance,
                    'status' => $ledger->cashbook->status,
                ] : null,
                'cashbook_transaction' => $ledger->transaction ? [
                    'id' => $ledger->transaction->id,
                    'cashbook_id' => $ledger->transaction->cashbook_id,
                    'reference_no' => $ledger->transaction->reference_no,
                    'transaction_type' => $ledger->transaction->transaction_type,
                    'category' => $ledger->transaction->category,
                    'transaction_datetime' => $ledger->transaction->transaction_datetime?->toDateTimeString(),
                    'currency_id' => $ledger->transaction->currency_id,
                    'amount' => $ledger->transaction->amount,
                    'remark' => $ledger->transaction->remark,
                    'description' => $ledger->transaction->description,
                    'status' => $ledger->transaction->status,
                ] : null,
                'transaction_datetime' => $ledger->transaction_datetime?->toDateTimeString(),
                'description' => $ledger->description ?? $ledger->transaction?->description,
                'remark' => $ledger->remark,
                'transaction_type' => $ledger->transaction_type,
                'amount' => $amount,
                'balance' => $runningBalance,
            ];
        }

        return [
            'date' => $selectedDate->toDateString(),
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'cashbook_id' => $cashbookId,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'data' => $rows,
            'meta' => [
                'total_transactions' => $ledgers->count(),
            ],
        ];
    }
}
