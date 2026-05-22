<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounting\app\Models\Account;
use Modules\Organization\app\Models\Currency;
// use Modules\Accounting\Database\Factories\JournalPostingFactory;

class JournalPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'type',
        'currency_id',
        'amount',
        'base_currency_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'base_currency_amount' => 'decimal:8',
    ];


    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }
}
