<?php

namespace Modules\Stakeholder\app\Models;

use Illuminate\Database\Eloquent\Model;

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
}