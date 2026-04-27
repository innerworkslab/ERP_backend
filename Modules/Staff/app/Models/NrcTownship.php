<?php

namespace Modules\Staff\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Staff\Database\Factories\NrcTownshipFactory;

class NrcTownship extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_en',
        'name_mm',
        'nrc_code',
    ];

    // protected static function newFactory(): NrcTownshipFactory
    // {
    //     // return NrcTownshipFactory::new();
    // }
}
