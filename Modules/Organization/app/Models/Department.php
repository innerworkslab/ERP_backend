<?php

namespace Modules\Organization\app\Models;

use App\Models\User;
use Carbon\Carbon;
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

    public function toArray()
    {
        $attributes = parent::toArray();
        if (array_key_exists('created_at', $attributes)) {
            $attributes['created_at'] = Carbon::parse($attributes['created_at'])->format('Y-m-d H:i:s');
        }
        if (array_key_exists('updated_at', $attributes)) {
            $attributes['updated_at'] = Carbon::parse($attributes['updated_at'])->format('Y-m-d H:i:s');
        }
        return $attributes;
    }

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
