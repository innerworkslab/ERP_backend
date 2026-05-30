<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\app\Models\Product;
// use Modules\Inventory\Database\Factories\GoodsReceiveNotesLineFactory;

class GoodsReceiveNotesLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'goods_receive_note_id',
        'purchase_order_line_id',
        'product_id',
        'uom_id',
        'ordered_quantity',
        'previously_received_quantity',
        'remaining_quantity',
        'received_quantity',
        'good_quantity',
        'short_quantity',
        'discrepancy_reason',
        'defect_responsibility',
        'unit_price',
        'line_weight',
        'allocated_charge_amount',
        'allocated_tax_amount',
        'final_unit_cost',
        'line_total',
        'remarks',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceiveNotes::class, 'goods_receive_note_id');
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

    // protected static function newFactory(): GoodsReceiveNotesLineFactory
    // {
    //     // return GoodsReceiveNotesLineFactory::new();
    // }
}
