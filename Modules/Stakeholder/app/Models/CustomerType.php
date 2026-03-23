<?php

namespace Modules\Stakeholder\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Stakeholder\Database\Factories\CustomerTypeFactory;

class CustomerType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

    protected $table = 'customer_types';

    // protected static function newFactory(): CustomerTypeFactory
    // {
    //     // return CustomerTypeFactory::new();
    // }
}
