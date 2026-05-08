<?php

namespace Modules\Stakeholder\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\app\Models\City;
use Modules\Location\app\Models\State;
use Modules\Organization\app\Models\Branch;
use Modules\Stakeholder\app\Models\CustomerBankAccount;
use Modules\Stakeholder\app\Models\CustomerType;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'code',
        'name',
        'company_name',
        'phone_number',
        'state_id',
        'city_id',
        'address',
        'bank_account_id',
        'credit_limit',
        'opening',
        'customer_type_id',
        'birthday',
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

    public function bank_account()
    {
        return $this->belongsTo(CustomerBankAccount::class, 'bank_account_id');
    }

    public function bank_accounts()
    {
        return $this->hasMany(CustomerBankAccount::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'customer_branches', 'customer_id', 'branch_id')->withTimestamps();
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
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
