<?php

namespace Modules\AccessControl\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\AccessControl\Database\Factories\FeatureUserFactory;

class FeatureUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'feature_id'];

    // protected static function newFactory(): FeatureUserFactory
    // {
    //     // return FeatureUserFactory::new();
    // }
}
