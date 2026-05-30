<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Currency;

class GoodsReceiveNoteCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receive_note_id',
        'charge_type',
        'currency_id',
        'amount',
        'base_amount',
        'description',
    ];

    public function grn()
    {
        return $this->belongsTo(GoodsReceiveNotes::class, 'goods_receive_note_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
