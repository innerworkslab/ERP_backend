<?php

namespace Modules\Product\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Organization\app\Models\Currency;
use Modules\Product\app\Models\CollectionItem;
use Modules\Product\app\Models\Product;
// use Modules\Product\Database\Factories\CollectionFactory;

class Collection extends Model
{
    use HasFactory;

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'purchase_price',
        'purchase_currency_id',
        'purchase_tax_id',
        'purchase_uom_id',
        'sale_price',
        'sale_currency_id',
        'sale_tax_id',
        'sale_uom_id',
        'status',
        'created_by',
        'updated_by',
    ];

    // protected static function newFactory(): CollectionFactory
    // {
    //     // return CollectionFactory::new();
    // }

    public function collectionItems()
    {
        return $this->hasMany(CollectionItem::class, 'collection_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_items', 'collection_id', 'product_id')
            ->using(CollectionItem::class)
            ->withPivot(['id', 'product_qty'])
            ->withTimestamps();
    }

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function purchase_currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'purchase_currency_id');
    }

    public function purchase_tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'purchase_tax_id');
    }

    public function purchase_uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'purchase_uom_id');
    }

    public function sale_currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'sale_currency_id');
    }

    public function sale_tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'sale_tax_id');
    }

    public function sale_uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'sale_uom_id');
    }
}
