<?php

namespace Modules\Accounting\app\Http\Repositories;

use RuntimeException;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Accounting\app\Models\CashbookTransfer;
use Modules\Accounting\app\Models\JournalEntry;
use Modules\Accounting\app\Models\JournalPosting;
use Modules\Organization\app\Models\Currency;

class CashbookTransferRepository extends BaseRepo
{
    public function __construct(CashbookTransfer $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        $data = $this->model->find($id);

        if ($data) {
            $data->load([
                'sourceCashbook.branch',
                'sourceCashbook.account',
                'sourceCashbook.currency',
                'destinationCashbook.branch',
                'destinationCashbook.account',
                'destinationCashbook.currency',
                'currency',
                'created_by',
                'updated_by',
            ]);
        }

        return $data;
    }

    public function create(array $data)
    {
        $sourceCashbook = Cashbook::query()->find($data['source_cashbook_id']);
        $destinationCashbook = Cashbook::query()->find($data['destination_cashbook_id']);

        $this->assertTransferParticipants($sourceCashbook, $destinationCashbook);
        $this->assertCurrencyMatchesCashbooks($sourceCashbook, $destinationCashbook, (int) $data['currency_id']);

        $selectedCurrency = Currency::query()->findOrFail($data['currency_id']);

        $data['reference_no'] = $this->generateReferenceNo();
        $data['transfer_datetime'] = now();
        $data['base_currency_amount'] = (float) $data['amount'] * (float) $selectedCurrency->exchange_rate;
        $data['status'] = 'pending';

        $transfer = $this->model->create($data);

        return $this->find($transfer->id);
    }

    public function update($id, array $data)
    {
        $transfer = $this->model->find($id);

        if (!$transfer) {
            return null;
        }

        if ($transfer->status !== 'pending') {
            throw new RuntimeException('Only pending cashbook transfer can be updated');
        }

        $sourceCashbook = Cashbook::query()->find($data['source_cashbook_id']);
        $destinationCashbook = Cashbook::query()->find($data['destination_cashbook_id']);

        $this->assertTransferParticipants($sourceCashbook, $destinationCashbook);
        $this->assertCurrencyMatchesCashbooks($sourceCashbook, $destinationCashbook, (int) $data['currency_id']);

        $selectedCurrency = Currency::query()->findOrFail($data['currency_id']);

        $data['base_currency_amount'] = (float) $data['amount'] * (float) $selectedCurrency->exchange_rate;
        $data['transfer_datetime'] = now();

        $transfer->update($data);

        return $this->find($transfer->id);
    }

    public function confirmTransfer(int $id)
    {
        $transfer = $this->model->query()->with(['sourceCashbook', 'destinationCashbook', 'currency'])->find($id);

        if (!$transfer) {
            return null;
        }

        if ($transfer->status === 'confirmed') {
            throw new RuntimeException('Cashbook transfer already confirmed');
        }

        if ($transfer->status === 'rejected') {
            throw new RuntimeException('Rejected cashbook transfer cannot be confirmed');
        }

        $sourceCashbook = Cashbook::query()->lockForUpdate()->find($transfer->source_cashbook_id);
        $destinationCashbook = Cashbook::query()->lockForUpdate()->find($transfer->destination_cashbook_id);

        $this->assertTransferParticipants($sourceCashbook, $destinationCashbook);
        $this->assertCurrencyMatchesCashbooks($sourceCashbook, $destinationCashbook, (int) $transfer->currency_id);

        $amount = (float) $transfer->amount;
        $sourceBeforeBalance = (float) $sourceCashbook->current_balance;
        $destinationBeforeBalance = (float) $destinationCashbook->current_balance;

        if ($sourceBeforeBalance < $amount) {
            throw new RuntimeException('Insufficient source cashbook balance.');
        }

        $sourceAfterBalance = $sourceBeforeBalance - $amount;
        $destinationAfterBalance = $destinationBeforeBalance + $amount;

        $sourceCashbook->update([
            'current_balance' => $sourceAfterBalance,
            'updated_by' => auth()->user()->id,
        ]);

        $destinationCashbook->update([
            'current_balance' => $destinationAfterBalance,
            'updated_by' => auth()->user()->id,
        ]);

        $journalEntry = JournalEntry::create([
            'journal_datetime' => now(),
            'source_type' => CashbookTransfer::class,
            'source_id' => $transfer->id,
            'description' => 'Cashbook transfer ' . $transfer->reference_no,
        ]);

        $this->createJournalPosting($journalEntry->id, (int) $sourceCashbook->account_id, 'credit', (int) $transfer->currency_id, $amount, (float) $transfer->base_currency_amount );

        $this->createJournalPosting($journalEntry->id, (int) $destinationCashbook->account_id, 'debit', (int) $transfer->currency_id, $amount, (float) $transfer->base_currency_amount );

        $transfer->update([
            'status' => 'confirmed',
            'updated_by' => auth()->user()->id,
        ]);

        return $this->find($transfer->id);
    }

    public function rejectTransfer(int $id)
    {
        $transfer = $this->model->find($id);

        if (!$transfer) {
            return null;
        }

        if ($transfer->status === 'rejected') {
            throw new RuntimeException('Cashbook transfer already rejected');
        }

        if ($transfer->status === 'confirmed') {
            throw new RuntimeException('Confirmed cashbook transfer cannot be rejected');
        }

        $transfer->update([
            'status' => 'rejected',
            'updated_by' => auth()->user()->id,
        ]);

        return $this->find($transfer->id);
    }

    public function getLastRecord()
    {
        return $this->model->orderByDesc('id')->first();
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
                $query->whereDate('transfer_datetime', '>=', $condition);
                continue;
            }

            if ($key === 'to_date') {
                $query->whereDate('transfer_datetime', '<=', $condition);
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

    public function whereFirst($column, $value)
    {
        $data = $this->model->where($column, $value)->first();

        if ($data) {
            $data->load([
                'sourceCashbook.branch',
                'sourceCashbook.account',
                'sourceCashbook.currency',
                'destinationCashbook.branch',
                'destinationCashbook.account',
                'destinationCashbook.currency',
                'currency',
                'created_by',
                'updated_by',
            ]);
        }

        return $data;
    }

    private function generateReferenceNo(): string
    {
        $prefix = 'CBTF-' . now()->format('Ymd') . '-';

        $latestTransfer = $this->model->where('reference_no', 'LIKE', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if (!$latestTransfer) {
            return $prefix . '000001';
        }

        $lastSequence = (int) substr($latestTransfer->reference_no, strrpos($latestTransfer->reference_no, '-') + 1);

        return $prefix . str_pad((string) ($lastSequence + 1), 6, '0', STR_PAD_LEFT);
    }

    private function assertTransferParticipants(?Cashbook $sourceCashbook, ?Cashbook $destinationCashbook): void
    {
        if (!$sourceCashbook) {
            throw new RuntimeException('Source cashbook not found.');
        }

        if (!$destinationCashbook) {
            throw new RuntimeException('Destination cashbook not found.');
        }

        if ((int) $sourceCashbook->id === (int) $destinationCashbook->id) {
            throw new RuntimeException('Source and destination cashbooks must be different.');
        }
    }

    private function assertCurrencyMatchesCashbooks(Cashbook $sourceCashbook, Cashbook $destinationCashbook, int $currencyId): void
    {
        if ((int) $sourceCashbook->currency_id !== $currencyId) {
            throw new RuntimeException('Transfer currency must match source cashbook currency.');
        }

        if ((int) $destinationCashbook->currency_id !== $currencyId) {
            throw new RuntimeException('Transfer currency must match destination cashbook currency.');
        }
    }

    private function createJournalPosting( int $journalEntryId, int $accountId, string $type, int $currencyId, float $amount, float $baseCurrencyAmount ): JournalPosting 
    {
        return JournalPosting::create([
            'journal_entry_id' => $journalEntryId,
            'account_id' => $accountId,
            'type' => $type,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'base_currency_amount' => $baseCurrencyAmount,
        ]);
    }
}
