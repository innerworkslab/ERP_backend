<?php

namespace Modules\Accounting\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\app\Models\CashbookLedger;
use Modules\Accounting\app\Models\CashbookTransactionAttachment;
use Modules\Accounting\app\Models\JournalEntry;
use Modules\Organization\app\Models\Currency;
// use Modules\Accounting\Database\Factories\CashbookTransactionFactory;

class CashbookTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cashbook_id',
        'source_account_id',
        'destination_account_id',
        'transaction_type',
        'category',
        'transaction_datetime',
        'currency_id',
        'amount',
        'base_currency_amount',
        'reference_no',
        'remark',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_datetime' => 'datetime',
        'amount' => 'decimal:8',
        'base_currency_amount' => 'decimal:8',
    ];

    public function cashbook()
    {
        return $this->belongsTo(Cashbook::class);
    }

    public function source_account()
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function destination_account()
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function attachments()
    {
        return $this->hasMany(
            CashbookTransactionAttachment::class
        );
    }

    public function ledger()
    {
        return $this->hasOne(CashbookLedger::class);
    }

    public function journalEntry()
    {
        return $this->morphOne(
            JournalEntry::class,
            'source'
        );
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
