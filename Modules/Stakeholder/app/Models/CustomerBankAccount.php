<?php

namespace Modules\Stakeholder\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Stakeholder\Database\Factories\CustomerBankAccountFactory;

class CustomerBankAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'bank_name',
        'account_number',
        'holder_name',
    ];

    protected $table = 'customer_bank_accounts';

    // protected static function newFactory(): CustomerBankAccountFactory
    // {
    //     // return CustomerBankAccountFactory::new();
    // }
}
