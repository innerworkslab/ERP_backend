<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\app\Models\Product;
// use Modules\Inventory\Database\Factories\ProductLotsFactory;

class ProductLots extends Model
{
    use HasFactory;

    protected $table = 'product_lots';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'lot_no',
        'expired_date',
        'serial_no',
    ];

    // protected static function newFactory(): ProductLotsFactory
    // {
    //     // return ProductLotsFactory::new();
    // }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
