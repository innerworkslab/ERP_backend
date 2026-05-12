<?php

namespace Modules\Stakeholder\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\app\Models\City;
use Modules\Location\app\Models\State;
use Modules\Stakeholder\app\Models\SupplierBankAccount;
use Modules\Stakeholder\app\Models\SupplierType;

class Supplier extends Model
{
    protected $table = 'suppliers';

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
        'supplier_type_id',
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

    public function supplier_type()
    {
        return $this->belongsTo(SupplierType::class);
    }

    public function bank_account()
    {
        return $this->belongsTo(SupplierBankAccount::class, 'bank_account_id');
    }
    public function bank_accounts()
    {
        return $this->hasMany(SupplierBankAccount::class, 'supplier_id');
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
