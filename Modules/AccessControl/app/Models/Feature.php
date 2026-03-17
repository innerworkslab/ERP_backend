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
        'description'
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class
        );
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
        );
    }
}
