<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\Tax;
// use Modules\Inventory\Database\Factories\PurchaseOrderLineFactory;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'gross_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_id',
        'tax_amount',
        'line_total',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
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
