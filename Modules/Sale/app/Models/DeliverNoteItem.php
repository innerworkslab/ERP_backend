<?php

namespace Modules\Sale\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\app\Models\UnitOfMeasurement;
use Modules\Product\app\Models\Product;

class DeliverNoteItem extends Model
{
    use HasFactory;

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected $fillable = [
        'deliver_note_id',
        'sale_invoice_item_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'total_price',
        'remark',
    ];

    public function deliverNote(): BelongsTo
    {
        return $this->belongsTo(DeliverNote::class);
    }

    public function saleInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SaleInvoiceItem::class);
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
