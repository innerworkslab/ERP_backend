<?php

namespace Modules\Inventory\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Organization\app\Models\Branch;
// use Modules\Inventory\Database\Factories\InventoryFactory;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    // protected static function newFactory(): InventoryFactory
    // {
    //     // return InventoryFactory::new();
    // }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_inventory')
            ->withPivot('status')
            ->withTimestamps();
    }
}
