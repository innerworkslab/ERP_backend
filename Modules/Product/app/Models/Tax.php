<?php

namespace Modules\Product\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Modules\Product\Database\Factories\TaxFactory;

class Tax extends Model
{
    use HasFactory;

    protected $table = 'taxs';

    protected $fillable = [
        'category',
        'code',
        'type',
        'amount',
        'status',
        'created_by',
        'updated_by',
    ];

    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // protected static function newFactory(): TaxFactory
    // {
    //     // return TaxFactory::new();
    // }
}
