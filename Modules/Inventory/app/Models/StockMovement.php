<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\app\Models\UnitOfMeasurement;
// use Modules\Inventory\Database\Factories\StockMovementFactory;

class StockMovement extends Model
{
    use HasFactory;

    public const REFERENCE_OPENING_STOCK = 'opening_stock';
    public const REFERENCE_TRANSFER_IN = 'transfer_in';
    public const REFERENCE_TRANSFER_OUT = 'transfer_out';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'transaction_date',
        'reference_type',
        'reference_id',
        'voucher_no',
        'product_id',
        'sku',
        'lot_no',
        'inventory_id',
        'movement_type',
        'quantity',
        'uom_id',
        'unit_cost',
        'total_cost',
        'balance_quantity_before',
        'balance_quantity_after',
        'balance_cost_before',
        'balance_cost_after'
    ];

    // protected static function newFactory(): StockMovementFactory
    // {
    //     // return StockMovementFactory::new();
    // }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class, 'reference_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasurement::class);
    }

    public function getFormattedQuantityAttribute()
    {
        $qty = (float) $this->quantity;

        if ($qty > 0) {
            return '+' . $qty;
        }

        return (string) $qty;
    }

    public function resolveReference()
    {
        return match ($this->reference_type) {
            self::REFERENCE_OPENING_STOCK => OpeningStock::query()->where('voucher_no', $this->voucher_no)->first(),
            self::REFERENCE_TRANSFER_IN, self::REFERENCE_TRANSFER_OUT => $this->reference_id ? StockTransfer::query()->find($this->reference_id) : StockTransfer::query()->where('reference_id', $this->voucher_no)->first(),
            default => null,
        };
    }
}
