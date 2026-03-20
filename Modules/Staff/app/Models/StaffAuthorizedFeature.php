<?php

namespace Modules\Staff\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\AccessControl\app\Models\Feature;

class StaffAuthorizedFeature extends Model
{
    protected $table = 'staff_authorized_features';

    protected $guarded = [];

    protected $casts = [
        'is_recommended' => 'boolean',
        'assigned_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class , 'staff_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
