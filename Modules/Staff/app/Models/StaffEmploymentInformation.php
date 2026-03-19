<?php

namespace Modules\Staff\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StaffEmploymentInformation extends Model
{
    protected $table = 'staff_employment_informations';

    protected $guarded = [];

    protected $casts = [
        'join_date' => 'date',
        'is_contract' => 'boolean',
        'salary' => 'decimal:2',
        'sale_incentive_amount' => 'decimal:2',
        'sale_commission' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
