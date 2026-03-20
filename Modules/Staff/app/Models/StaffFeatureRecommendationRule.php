<?php

namespace Modules\Staff\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\AccessControl\app\Models\Feature;
use Modules\AccessControl\app\Models\Role;
use Modules\Organization\app\Models\Department;

class StaffFeatureRecommendationRule extends Model
{
    protected $table = 'staff_feature_recommendation_rules';

    protected $guarded = [];

    protected $casts = [
        'is_default_recommended' => 'boolean',
        'status' => 'string',
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

}
