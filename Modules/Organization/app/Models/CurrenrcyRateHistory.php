<?php

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Currency;
// use Modules\Organization\Database\Factories\CurrencyRateHistoryFactory;

class CurrencyRateHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'currency_id', 'exchange_rate', 'rate_date'
    ];

    // protected static function newFactory(): CurrencyRateHistoryFactory
    // {
    //     // return CurrencyRateHistoryFactory::new();
    // }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
