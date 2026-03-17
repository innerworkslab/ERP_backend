<?php

namespace Modules\Organization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\AccessControl\app\Models\Role;
// use Modules\Organization\Database\Factories\DepartmentFactory;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'branch_id',
        'description',
        'status'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
