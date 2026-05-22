<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Accounting\app\Models\CashbookTransaction;
// use Modules\Accounting\Database\Factories\CashbookLedgerFactory;

class CashbookLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashbook_id',
        'cashbook_transaction_id',
        'transaction_datetime',
        'transaction_type',
        'amount',
        'before_balance',
        'after_balance',
        'remark',
        'description',
    ];

    protected $casts = [
        'transaction_datetime' => 'datetime',
        'amount' => 'decimal:8',
        'before_balance' => 'decimal:8',
        'after_balance' => 'decimal:8',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function cashbook()
    {
        return $this->belongsTo(Cashbook::class);
    }

    public function transaction()
    {
        return $this->belongsTo(
            CashbookTransaction::class,
            'cashbook_transaction_id'
        );
    }
}
