<?php

namespace Modules\Sale\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\app\Models\Cashbook;
use Modules\Inventory\app\Models\Inventory;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Currency;
use Modules\PriceGroup\app\Models\SellingPriceGroup;
use Modules\Product\app\Models\Tax;
use Modules\Stakeholder\app\Models\Customer;

class SaleInvoice extends Model
{
    use HasFactory;

    protected $casts = [
        'invoice_date' => 'datetime',
        'payment_due_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'sub_total' => 'decimal:2',
        'items_discount' => 'decimal:2',
        'invoice_discount_amount' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'branch_id',
        'inventory_id',
        'customer_id',
        'currency_id',
        'exchange_rate',
        'selling_price_group_id',
        'payment_terms',
        'payment_due_date',
        'status',
        'remarks',
        'sell_tax_id',
        'sub_total',
        'items_discount',
        'invoice_discount_type',
        'invoice_discount_amount',
        'tax_total',
        'delivery_charge',
        'grand_total',
        'payment_status',
        'paid_amount',
        'cashbook_id',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function selling_price_group(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class, 'selling_price_group_id');
    }

    public function sell_tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'sell_tax_id');
    }

    public function cashbook(): BelongsTo
    {
        return $this->belongsTo(Cashbook::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleInvoiceItem::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(SaleInvoiceDelivery::class);
    }
}
