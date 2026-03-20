<?php

namespace Modules\Stakeholder\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Branch;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'code',
        'name',
        'company_name',
        'phone_number',
        'country',
        'town',
        'township',
        'address',
        'bank_acc',
        'branch_id',
        'credit_limit',
        'opening',
        'type',
        'birthday',
        'payment_terms',
        'payment_due',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening' => 'decimal:2',
        'birthday' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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