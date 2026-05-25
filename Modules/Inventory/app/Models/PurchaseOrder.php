<?php

namespace Modules\Inventory\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\app\Models\Inventory;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Currency;
use Modules\Stakeholder\app\Models\Supplier;
// use Modules\Inventory\Database\Factories\PurchaseOrderFactory;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'po_number',
        'po_date',
        'supplier_id',
        'branch_id',
        'inventory_id',
        'currency_id',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'status',
        'payment_status',
        'delivery_status',
        'remarks',
        'created_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }
}
