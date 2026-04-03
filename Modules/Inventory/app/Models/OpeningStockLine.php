<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\app\Models\Product;
// use Modules\Inventory\Database\Factories\OpeningStockLineFactory;

class OpeningStockLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'opening_stock_id',
        'product_id',
        'quantity',
        'uom_id',
        'purchase_price',
        'subtotal',
        'lot_no',
        'expired_date',
        'serial_no',
        'remarks',
    ];

    public function openingStock(): BelongsTo
    {
        return $this->belongsTo(OpeningStock::class, 'opening_stock_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'uom_id');
    }

    // protected static function newFactory(): OpeningStockLineFactory
    // {
    //     // return OpeningStockLineFactory::new();
    // }
}
