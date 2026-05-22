<?php

namespace Modules\Accounting\app\Http\Repositories;

use DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\app\Http\Repositories\BaseRepo;
use Modules\Accounting\app\Models\Account;
use Modules\Accounting\app\Models\CashbookTransaction;
use Modules\Organization\app\Models\Currency;


class CashbookTransactionRepository extends BaseRepo
{
    protected $account_repository;

    public function __construct(CashbookTransaction $model, AccountRepository $account_repository)
    {
        parent::__construct($model);
        $this->account_repository = $account_repository;
    }

    public function toggleActive(CashbookTransaction $cashbook)
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
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['cashbook', 'currency', 'source_account', 'destination_account', 'created_by', 'updated_by', 'attachments']);
        }
        return $data;
    }

    public function create($data)
    {
        $reference_no = $this->generateCashbookTransactionReferenceCode();
        $data['reference_no'] = $reference_no;
        $selected_currency = Currency::find($data['currency_id']);
        $base_currency_amount = $data['amount'] * $selected_currency->exchange_rate;
        $data['base_currency_amount'] = $base_currency_amount;
        $data['transaction_datetime'] = now();

        $transaction_record = $this->model->create($data);
        if (isset($data['attachments'])) {
            $this->storeAttachments($transaction_record, $data['attachments']);
        }
        return $transaction_record;
    }

    public function update($id, $data)
    {

        $transaction = $this->model->find($id);
        if (isset($data['currency_id'], $data['amount'])) {

            $selectedCurrency = Currency::find($data['currency_id']);

            $data['base_currency_amount'] =
                $data['amount'] * $selectedCurrency->exchange_rate;
        }
        $data['transaction_datetime'] = now();
        $transaction->update($data);

        $existingAttachmentIds = $data['existing_attachment_ids'] ?? [];

        $attachmentsToDelete = $transaction->attachments()
            ->whereNotIn('id', $existingAttachmentIds)
            ->get();

        foreach ($attachmentsToDelete as $attachment) {

            if (
                $attachment->file_path &&
                Storage::disk('public')->exists($attachment->file_path)
            ) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $attachment->delete();
        }

        if (!empty($data['attachments'])) {

            $this->storeAttachments(
                $transaction,
                $data['attachments']
            );
        }
        return $transaction;
    }

    public function generateCashbookTransactionReferenceCode(): string
    {
        $datePrefix = now()->format('Ymd');

        $prefix = "CBT-{$datePrefix}-";

        $latestTransaction = CashbookTransaction::where('reference_no', 'LIKE', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latestTransaction) {
            return $prefix . '000001';
        }

        $parts = explode('-', $latestTransaction->reference_no);

        $lastSequence = (int) end($parts);

        $nextSequence = $lastSequence + 1;

        return $prefix . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
    }

    public function storeAttachments(
        CashbookTransaction $transaction,
        array $files = []
    ): void {
        DB::transaction(function () use ($transaction, $files) {

            foreach ($files as $file) {

                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $path = $file->store(
                    'cashbook-transactions',
                    'public'
                );

                $attachmentType = $this->detectAttachmentType(
                    $file->getClientOriginalExtension()
                );

                $transaction->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),

                    'file_path' => $path,

                    'file_type' => $file->getMimeType(),

                    'extension' => $file->getClientOriginalExtension(),

                    'attachment_type' => $attachmentType,

                    'file_size' => $file->getSize(),
                ]);
            }
        });
    }

    protected function detectAttachmentType(
        string $extension
    ): string {

        $extension = strtolower($extension);

        return match ($extension) {

            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
            => 'image',

            'pdf'
            => 'pdf',

            'xls',
            'xlsx',
            'csv'
            => 'spreadsheet',

            'doc',
            'docx',
            'txt'
            => 'document',

            default
            => 'other',
        };
    }
}
