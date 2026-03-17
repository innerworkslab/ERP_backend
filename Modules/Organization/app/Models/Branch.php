<?php

namespace Modules\Organization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\AccessControl\app\Models\Role;
// use Modules\Organization\Database\Factories\BranchFactory;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefix',
        'name',
        'location',
        'status'
    ];

    public function departments()
    {
        return $this->hasMany(Department::class);
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
