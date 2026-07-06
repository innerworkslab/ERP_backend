<?php

namespace Modules\Sale\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleInvoiceDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_invoice_id',
        'delivery_provider_id',
        'delivery_charge_paid',
        'delivery_charge',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_note',
    ];

    public function sale_invoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class);
    }

    public function delivery_provider(): BelongsTo
    {
        return $this->belongsTo(DeliveryProvider::class);
    }
}
