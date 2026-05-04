<?php

namespace Modules\Stakeholder\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Stakeholder\Database\Factories\SupplierTypeFactory;

class SupplierType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

    protected $table = 'supplier_types';

    // protected static function newFactory(): SupplierTypeFactory
    // {
    //     // return SupplierTypeFactory::new();
    // }
}
