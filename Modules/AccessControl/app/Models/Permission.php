<?php

namespace Modules\AccessControl\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\AccessControl\Database\Factories\PermissionFactory;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_id',
        'name',
        'key',
        'description',
        'status'
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
