<?php

namespace Modules\Product\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Modules\Product\Database\Factories\CategoryFactory;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function variations()
    {
        return $this->belongsToMany(
            Variation::class,
            'category_variation',
            'category_id',
            'variation_id'
        )->withTimestamps();
    }
    // protected static function newFactory(): CategoryFactory
    // {
    //     // return CategoryFactory::new();
    // }
}
