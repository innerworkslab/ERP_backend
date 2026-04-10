<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Inventory\app\Models\StockTransfer;
use Modules\Product\app\Models\Product;
// use Modules\Inventory\Database\Factories\StockTransferLineFactory;

class StockTransferLine extends Model
{
    use HasFactory;

    protected $table = 'stock_transfer_line';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'lot_no',
        'quantity',
        'uom_id',
        'remarks'
    ];

    // protected static function newFactory(): StockTransferLineFactory
    // {
    //     // return StockTransferLineFactory::new();
    // }

    public function stock_transfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'uom_id');
    }
}
