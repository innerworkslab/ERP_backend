<?php

namespace Modules\Staff\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StaffBankingInformation extends Model
{
    protected $table = 'staff_banking_informations';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
