<?php

namespace Modules\Accounting\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Currency;
// use Modules\Accounting\Database\Factories\CashbookFactory;

class Cashbook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'account_id',
        'currency_id',
        'name',
        'type',
        'current_balance',
        'status',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:8',
        'current_balance' => 'decimal:8',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // public function transactions()
    // {
    //     return $this->hasMany(CashbookTransaction::class);
    // }

    // public function ledgers()
    // {
    //     return $this->hasMany(CashbookLedger::class);
    // }
}
