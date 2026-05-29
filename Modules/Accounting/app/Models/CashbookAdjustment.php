<?php

namespace Modules\Accounting\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Organization\app\Models\Branch;
// use Modules\Accounting\Database\Factories\CashbookAdjustmentFactory;

class CashbookAdjustment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'branch_id',
        'reference_no',
        'cashbook_id',
        'type',
        'amount',
        'reason',
        'status',
        'approved_by',
        'created_by',
        'approved_at',
    ];

    // protected static function newFactory(): CashbookAdjustmentFactory
    // {
    //     // return CashbookAdjustmentFactory::new();
    // }

    public function cashbook()
    {
        return $this->belongsTo(Cashbook::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
