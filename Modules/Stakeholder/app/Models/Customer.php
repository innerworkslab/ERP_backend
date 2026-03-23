<?php

namespace Modules\Stakeholder\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Branch;
use Modules\Stakeholder\app\Models\CustomerType;

class Customer extends Model
{
    protected $table = 'customers';

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
        'customer_type_id',
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

    public function customer_type()
    {
        return $this->belongsTo(CustomerType::class);
    }

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