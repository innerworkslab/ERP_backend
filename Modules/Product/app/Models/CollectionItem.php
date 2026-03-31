<?php

namespace Modules\Product\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Product\app\Models\Collection;
use Modules\Product\app\Models\Product;
// use Modules\Product\Database\Factories\CollectionItemFactory;

class CollectionItem extends Pivot
{
    use HasFactory;

    protected $table = 'collection_items';

    public $incrementing = true;

    protected $fillable = [
        'collection_id',
        'product_id',
        'product_qty',
    ];

    protected $casts = [
        'product_qty' => 'decimal:2',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
