<?php

namespace Modules\Staff\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StaffPersonalInformation extends Model
{
    protected $table = 'staff_personal_informations';

    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
