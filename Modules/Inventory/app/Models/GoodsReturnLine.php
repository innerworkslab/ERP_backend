<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\Tax;

class GoodsReturnLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'goods_receive_note_line_id',
        'purchase_order_line_id',
        'product_id',
        'uom_id',
        'tax_id',
        'return_quantity',
        'unit_price',
        'tax_amount',
        'line_total',
        'reason',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsReceiveNoteLine()
    {
        return $this->belongsTo(GoodsReceiveNotesLine::class, 'goods_receive_note_line_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'uom_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
}
