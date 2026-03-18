<?php

namespace Modules\Organization\app\Models;

use App\Models\User;
use Carbon\Carbon;
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

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
