<?php

namespace Modules\Inventory\app\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Modules\Inventory\Database\Factories\UnitOfMeasurementConversionFactory;

class UnitOfMeasurementConversion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'base_unit_id',
        'conversion_unit_id',
        'conversion_rate',
        'status',
        'created_by',
        'updated_by',
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

    public function baseUnit()
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'base_unit_id');
    }

    public function conversionUnit()
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'conversion_unit_id');
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
