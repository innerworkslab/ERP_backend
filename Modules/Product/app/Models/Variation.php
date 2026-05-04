<?php

namespace Modules\Product\app\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Modules\Product\Database\Factories\VariationFactory;

class Variation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'value_data_type',
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

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function productCategories()
    {
        return $this->belongsToMany(
            Category::class,
            'category_variation',
            'variation_id',
            'category_id'
        )->withTimestamps();
    }
}
