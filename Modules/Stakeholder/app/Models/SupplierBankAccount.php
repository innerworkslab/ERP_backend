<?php

namespace Modules\Stakeholder\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Stakeholder\Database\Factories\SupplierBankAccountFactory;

class SupplierBankAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'supplier_id',
        'bank_name',
        'account_number',
        'holder_name',
    ];

    protected $table = 'supplier_bank_accounts';

    // protected static function newFactory(): SupplierBankAccountFactory
    // {
    //     // return SupplierBankAccountFactory::new();
    // }
}
