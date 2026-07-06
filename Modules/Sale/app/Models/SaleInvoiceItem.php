<?php

namespace Modules\Sale\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Product\app\Models\Product;

class SaleInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_invoice_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'discount_type',
        'discount',
        'tax',
        'total',
        'remarks',
    ];

    public function sale_invoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'uom_id');
    }
}
