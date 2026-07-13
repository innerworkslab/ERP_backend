<?php

namespace Modules\Sale\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Product\app\Models\Product;

class SaleInvoiceItem extends Model
{
    use HasFactory;

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'order_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'previously_deliver_qty' => 'decimal:2',
        'remaining_delivery_qty' => 'decimal:2',
    ];

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
        'order_qty',
        'reserved_qty',
        'previously_deliver_qty',
        'remaining_delivery_qty',
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

    public function deliver_note_items(): HasMany
    {
        return $this->hasMany(DeliverNoteItem::class);
    }
}
