<?php

namespace Modules\PriceGroup\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\app\Models\Branch;
use Modules\PriceGroup\app\Models\SellingPriceGroup;
use Modules\Stakeholder\app\Models\CustomerType;

class SellingPriceGroupSeeder extends Seeder
{
    public function run(): void
    {
        $retailCustomerTypeId = CustomerType::query()
            ->where('name', 'Retail')
            ->value('id');

        $firstBranchId = Branch::query()
            ->orderBy('id')
            ->value('id');

        $sellingPriceGroups = [
            [
                'name' => 'Default Selling Price',
                'customer_type_id' => null,
                'branch_id' => null,
                'profit_margin_type' => 'percentage',
                'profit_margin_value' => 0,
                'status' => 'active',
            ],
            [
                'name' => 'Retail Selling Price',
                'customer_type_id' => $retailCustomerTypeId,
                'branch_id' => null,
                'profit_margin_type' => 'percentage',
                'profit_margin_value' => 5,
                'status' => 'active',
            ],
            [
                'name' => 'Branch Special Selling Price',
                'customer_type_id' => null,
                'branch_id' => $firstBranchId,
                'profit_margin_type' => 'fixed',
                'profit_margin_value' => 50000,
                'status' => 'active',
            ],
        ];

        foreach ($sellingPriceGroups as $sellingPriceGroup) {
            SellingPriceGroup::updateOrCreate(
                ['name' => $sellingPriceGroup['name']],
                $sellingPriceGroup
            );
        }
    }
}