<?php

namespace Modules\AccessControl\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\AccessControl\Database\Factories\FeatureFactory;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'key',
        'description'
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'feature_role',
            'feature_id',
            'role_id'
        );
    }

    public function permissions()
    {
        return $this->hasMany(
            Permission::class,
            'feature_id'
        );
    }
}
