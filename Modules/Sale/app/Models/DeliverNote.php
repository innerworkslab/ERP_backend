<?php

namespace Modules\Sale\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\app\Models\Inventory;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Currency;
use Modules\Stakeholder\app\Models\Customer;

class DeliverNote extends Model
{
    use HasFactory;

    protected $casts = [
        'delivery_date' => 'datetime',
    ];

    protected $fillable = [
        'deliver_note_no',
        'sale_invoice_id',
        'branch_id',
        'source_inventory_id',
        'customer_id',
        'currency_id',
        'delivery_provider_id',
        'delivery_date',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'delivery_note',
        'status',
    ];

    public function saleInvoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sourceInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'source_inventory_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function deliveryProvider(): BelongsTo
    {
        return $this->belongsTo(DeliveryProvider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliverNoteItem::class);
    }
}
