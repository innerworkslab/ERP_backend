<?php

namespace Modules\PriceGroup\app\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Branch;
use Modules\Stakeholder\app\Models\CustomerType;

class SellingPriceGroup extends Model
{
    protected $table = 'selling_price_groups';

    protected $fillable = [
        'name',
        'customer_type_id',
        'status',
    ];

    protected $casts = [
        'profit_margin_value' => 'decimal:2',
    ];

    public function customer_type(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'selling_price_group_branch')
            ->withTimestamps();
    }
}
