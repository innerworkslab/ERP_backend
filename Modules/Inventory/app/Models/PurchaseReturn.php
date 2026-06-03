<?php

namespace Modules\Inventory\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Currency;
use Modules\Stakeholder\app\Models\Supplier;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_no',
        'goods_receive_note_id',
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'inventory_id',
        'currency_id',
        'exchange_goods_receive_note_id',
        'return_date',
        'return_type',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'remarks',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    public function goodsReceiveNote()
    {
        return $this->belongsTo(GoodsReceiveNotes::class, 'goods_receive_note_id');
    }

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

    public function exchangeGoodsReceiveNote()
    {
        return $this->belongsTo(GoodsReceiveNotes::class, 'exchange_goods_receive_note_id');
    }

    public function lines()
    {
        return $this->hasMany(GoodsReturnLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
