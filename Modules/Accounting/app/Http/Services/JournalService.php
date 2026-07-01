<?php

namespace Modules\Accounting\app\Http\Services;

use Modules\Accounting\app\Models\JournalEntry;
use Modules\Accounting\app\Models\JournalPosting;

class JournalService
{
    public function createEntry(array $header, array $postings): JournalEntry
    {
        $entry = JournalEntry::create([
            'voucher_no' => $header['voucher_no'] ?? null,
            'journal_date' => $header['journal_date'] ?? now()->toDateString(),
            'journal_datetime' => $header['journal_datetime'] ?? now(),
            'source_type' => $header['source_type'],
            'source_id' => $header['source_id'],
            'description' => $header['description'] ?? null,
        ]);

        foreach ($postings as $posting) {
            JournalPosting::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $posting['account_id'],
                'type' => $posting['type'],
                'currency_id' => $posting['currency_id'],
                'amount' => $posting['amount'],
                'base_currency_amount' => $posting['base_currency_amount'],
            ]);
        }

        return $entry;
    }

    public function entryExists(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }
}
