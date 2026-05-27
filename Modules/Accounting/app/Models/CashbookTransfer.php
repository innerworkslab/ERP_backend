<?php

namespace Modules\Accounting\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Currency;

class CashbookTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_cashbook_id',
        'destination_cashbook_id',
        'amount',
        'currency_id',
        'base_currency_amount',
        'transfer_datetime',
        'reference_no',
        'remark',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transfer_datetime' => 'datetime',
        'amount' => 'decimal:8',
        'base_currency_amount' => 'decimal:8',
    ];

    public function sourceCashbook()
    {
        return $this->belongsTo(Cashbook::class, 'source_cashbook_id');
    }

    public function destinationCashbook()
    {
        return $this->belongsTo(Cashbook::class, 'destination_cashbook_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
