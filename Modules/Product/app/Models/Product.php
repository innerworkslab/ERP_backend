<?php

namespace Modules\Product\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Organization\app\Models\Currency;
use Modules\Product\app\Models\Collection;
use Modules\Product\app\Models\CollectionItem;
// use Modules\Product\Database\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'sku',
        'image',
        'image_path',
        'image_url',
        'category_id',
        'brand_id',
        'alert_quantity',
        'stock_uom_id',
        'purchase_price',
        'purchase_currency_id',
        'purchase_tax_id',
        'purchase_uom_id',
        'sale_price',
        'sale_currency_id',
        'sale_tax_id',
        'sale_uom_id',
        'origin_country_id',
        'created_by',
        'updated_by',
        'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function origin_country(): BelongsTo
    {
        return $this->belongsTo(OriginCountry::class, 'origin_country_id');
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

    public function stock_uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'stock_uom_id');
    }

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_items', 'product_id', 'collection_id')
            ->using(CollectionItem::class)
            ->withPivot(['id', 'product_qty'])
            ->withTimestamps();
    }

    // protected static function newFactory(): ProductFactory
    // {
    //     // return ProductFactory::new();
    // }
}
