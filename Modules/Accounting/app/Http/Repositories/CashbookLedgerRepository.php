<?php

namespace Modules\Accounting\app\Http\Repositories;

use Carbon\Carbon;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Accounting\app\Models\CashbookLedger;

class CashbookLedgerRepository extends BaseRepo
{
    public function __construct(CashbookLedger $model)
    {
        parent::__construct($model);
    }

    public function getDailyStatement(array $filters): array
    {
        $cashbookId = !empty($filters['cashbook_id'])
            ? (int) $filters['cashbook_id']
            : null;
        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = (int) ($filters['page'] ?? 1);
        $selectedDate = Carbon::parse($filters['date'] ?? now()->toDateString());
        $fromDate = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->startOfDay()
            : $selectedDate->copy()->startOfDay();
        $toDate = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->endOfDay()
            : $selectedDate->copy()->endOfDay();

        $openingBalance = null;
        $query = $this->model->query()
            ->with(['cashbook', 'transaction'])
            ->whereBetween('transaction_datetime', [$fromDate, $toDate]);

        if ($cashbookId !== null) {
            $openingLedger = $this->model->query()
                ->where('cashbook_id', $cashbookId)
                ->where('transaction_datetime', '<', $fromDate)
                ->orderByDesc('transaction_datetime')
                ->orderByDesc('id')
                ->first();

            $openingBalance = $openingLedger
                ? (float) $openingLedger->after_balance
                : 0.0;

            $query->where('cashbook_id', $cashbookId);
        }

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

        $runningBalances = [];
        $openingBalances = [];
        $rows = [];

        if ($cashbookId === null) {
            $cashbookIds = Cashbook::query()->pluck('id');

            foreach ($cashbookIds as $ledgerCashbookId) {
                $openingBalances[$ledgerCashbookId] = $this->getOpeningBalanceForCashbook(
                    (int) $ledgerCashbookId,
                    $fromDate
                );
            }

            $openingBalance = array_sum($openingBalances);
            $runningBalances = $openingBalances;
        }

        foreach ($ledgers as $ledger) {
            $amount = (float) $ledger->amount;
            $ledgerCashbookId = (int) $ledger->cashbook_id;

            if (!array_key_exists($ledgerCashbookId, $runningBalances)) {
                $runningBalances[$ledgerCashbookId] = $openingBalances[$ledgerCashbookId]
                    ?? $this->getOpeningBalanceForCashbook($ledgerCashbookId, $fromDate);
            }

            if ($ledger->transaction_type === 'in') {
                $runningBalances[$ledgerCashbookId] += $amount;
            } else {
                $runningBalances[$ledgerCashbookId] -= $amount;
            }

            $rows[] = [
                'id' => $ledger->id,
                'cashbook_id' => $ledger->cashbook_id,
                'cashbook_transaction_id' => $ledger->cashbook_transaction_id,
                'cashbook' => $ledger->cashbook ? [
                    'name' => $ledger->cashbook->name,
                ] : null,
                'cashbook_transaction' => $ledger->transaction ? [
                    'id' => $ledger->transaction->id,
                    'cashbook_id' => $ledger->transaction->cashbook_id,
                    'reference_no' => $ledger->transaction->reference_no,
                    'transaction_type' => $ledger->transaction->transaction_type,
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
                'balance' => $runningBalances[$ledgerCashbookId],
            ];
        }

        $totalCount = count($rows);
        $offset = max(0, $perPage * ($page - 1));
        $paginatedRows = array_slice($rows, $offset, $perPage);

        return [
            'date' => $selectedDate->toDateString(),
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'cashbook_id' => $cashbookId,
            'opening_balance' => $openingBalance,
            'closing_balance' => $cashbookId !== null
                ? ($totalCount > 0 ? $rows[$totalCount - 1]['balance'] : $openingBalance)
                : array_sum($runningBalances),
            'data' => $paginatedRows,
            'meta' => [
                'total_transactions' => $totalCount,
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($totalCount / $perPage),
            ],
        ];
    }

    private function getOpeningBalanceForCashbook(int $cashbookId, Carbon $fromDate): float
    {
        $openingLedger = $this->model->query()
            ->where('cashbook_id', $cashbookId)
            ->where('transaction_datetime', '<', $fromDate)
            ->orderByDesc('transaction_datetime')
            ->orderByDesc('id')
            ->first();

        return $openingLedger
            ? (float) $openingLedger->after_balance
            : 0.0;
    }
}
