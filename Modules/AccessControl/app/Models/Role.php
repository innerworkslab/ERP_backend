<?php

namespace Modules\AccessControl\app\Models;

use App\Models\User;
use Carbon\Carbon;
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
        // 'branch_id',
        'department_id',
        // 'is_super',
        'status',
        'created_by',
        'updated_by'
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

    public function parentRole()
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    public function children()
    {
        return $this->hasMany(Role::class, 'parent_role_id');
    }

    // public function branch()
    // {
    //     return $this->belongsTo(Branch::class);
    // }

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

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
