<?php

namespace Modules\PriceGroup\app\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Branch;
use Modules\Stakeholder\app\Models\CustomerType;

class SellingPriceGroup extends Model
{
    protected $table = 'selling_price_groups';

    protected $fillable = [
        'name',
        'customer_type_id',
        'branch_id',
        'profit_margin_type',
        'profit_margin_value',
        'status',
    ];

    protected $casts = [
        'profit_margin_value' => 'decimal:2',
    ];

    public function customer_type(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
