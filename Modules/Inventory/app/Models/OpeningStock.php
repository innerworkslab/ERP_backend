<?php

namespace Modules\Inventory\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\app\Models\OpeningStockLine;
// use Modules\Inventory\Database\Factories\OpeningStockFactory;

class OpeningStock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'voucher_no',
        'voucher_date',
        'inventory_id',
        'status',
        'remarks',
        'total_amount',
        'created_by',
        'updated_by',
        'confirmed_by',
        'confirmed_at',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OpeningStockLine::class, 'opening_stock_id');
    }

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmed_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // protected static function newFactory(): OpeningStockFactory
    // {
    //     // return OpeningStockFactory::new();
    // }
}
