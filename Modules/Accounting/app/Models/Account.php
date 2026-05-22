<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountFactory;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_account_id',
        'code',
        'name',
        'type',
        'division',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent Account
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    /**
     * Child Accounts
     */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }
}
