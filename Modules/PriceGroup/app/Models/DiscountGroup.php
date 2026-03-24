<?php

namespace Modules\PriceGroup\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\app\Models\Branch;
use Modules\Stakeholder\app\Models\CustomerType;
// use Modules\PriceGroup\Database\Factories\DiscountGroupFactory;

class DiscountGroup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'discount_groups';

    protected $fillable = [
        'name', 'customer_type_id', 'branch_id', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function customer_type(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // protected static function newFactory(): DiscountGroupFactory
    // {
    //     // return DiscountGroupFactory::new();
    // }
}
