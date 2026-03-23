<?php

namespace Modules\PriceGroup\app\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Modules\Organization\app\Models\Branch;
use Modules\Stakeholder\app\Models\CustomerType;

class PricingGroup extends Model
{
    protected $table = 'pricing_groups';

    protected $fillable = [
        'name','customer_type_id','branch_id','is_active'
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
}
