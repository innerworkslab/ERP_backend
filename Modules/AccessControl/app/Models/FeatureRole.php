<?php

namespace Modules\AccessControl\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\AccessControl\Database\Factories\FeatureRoleFactory;

class FeatureRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'feature_id',
    ];
}
