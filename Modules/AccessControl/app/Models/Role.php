<?php

namespace Modules\AccessControl\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Department;
// use Modules\AccessControl\Database\Factories\RoleFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_role_id',
        'branch_id',
        'department_id',
        // 'is_super',
        'status'
    ];

    public function parentRole()
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    public function children()
    {
        return $this->hasMany(Role::class, 'parent_role_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function features()
    {
        return $this->belongsToMany(
            Feature::class
        );
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
