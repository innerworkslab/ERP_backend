<?php

namespace Modules\Inventory\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Currency;
use Modules\Stakeholder\app\Models\Supplier;
// use Modules\Inventory\Database\Factories\GoodsReceiveNotesFactory;

class GoodsReceiveNotes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'grn_no',
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'inventory_id',
        'currency_id',
        'grn_date',
        'fee_allocation_method',
        'tax_allocation_method',
        'remarks',
        'subtotal_amount',
        'discount_amount',
        'cargo_tax_amount',
        'charge_total_amount',
        'total_amount',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

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

    public function lines()
    {
        return $this->hasMany(GoodsReceiveNotesLine::class, 'goods_receive_note_id');
    }

    public function charges()
    {
        return $this->hasMany(GoodsReceiveNoteCharge::class, 'goods_receive_note_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // protected static function newFactory(): GoodsReceiveNotesFactory
    // {
    //     // return GoodsReceiveNotesFactory::new();
    // }
}
