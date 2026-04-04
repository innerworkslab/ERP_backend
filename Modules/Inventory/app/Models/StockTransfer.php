<?php

namespace Modules\Inventory\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\app\Models\Inventory;
use Modules\Inventory\app\Models\StockTransferLine;
// use Modules\Inventory\Database\Factories\StockTransferFactory;

class StockTransfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'transfer_date',
        'reference_id',
        'source_inventory_id',
        'target_inventory_id',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'confirmed_by',
        'rejected_by',
        'confirmed_at',
        'rejected_at'
    ];

    // protected static function newFactory(): StockTransferFactory
    // {
    //     // return StockTransferFactory::new();
    // }

    public function lines()
    {
        return $this->hasMany(StockTransferLine::class);
    }

    public function source_inventory()
    {
        return $this->belongsTo(Inventory::class, 'source_inventory_id');
    }

    public function target_inventory()
    {
        return $this->belongsTo(Inventory::class, 'target_inventory_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmed_by()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejected_by()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
