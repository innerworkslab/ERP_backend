<?php

namespace Modules\AccessControl\database\seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\app\Models\Feature;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name' => 'Branch',
                'key' => 'branch_feature',
                'module' => 'Organization',
                'description' => 'This is a feature of branch',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Department',
                'key' => 'department_feature',
                'module' => 'Organization',
                'description' => 'This is a feature of department',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Role',
                'key' => 'role_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Feature',
                'key' => 'feature_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of feature',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'User',
                'key' => 'user_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Feature Recommendation Rule',
                'key' => 'feature_recommendation_rule_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of feature recommendation rule',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer',
                'key' => 'customer_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Supplier',
                'key' => 'supplier_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of supplier',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Price Group',
                'key' => 'price_group_feature',
                'module' => 'AccessControl',
                'description' => 'This is a feature of price group',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                    'name' => 'Discount Group',
                    'key' => 'discount_group_feature',
                    'module' => 'AccessControl',
                    'description' => 'This is a feature of discount group',
                    'created_at' => now(),
                    'updated_at' => now(),
            ],
            [
                'name' => 'Variation',
                'key' => 'variation_feature',
                'module' => 'Product',
                'description' => 'This is a feature of variation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'UOM',
                'key' => 'uom_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of uom',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'UOM Conversion',
                'key' => 'uom_conversion_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of uom conversion',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Currency',
                'key' => 'currency_feature',
                'module' => 'Organization',
                'description' => 'This is a feature of currency',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Brand',
                'key' => 'brand_feature',
                'module' => 'Product',
                'description' => 'This is a feature of brand',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Category',
                'key' => 'category_feature',
                'module' => 'Product',
                'description' => 'This is a feature of category',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Tax',
                'key' => 'tax_feature',
                'module' => 'Product',
                'description' => 'This is a feature of tax',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Location',
                'key' => 'location_feature',
                'module' => 'Location',
                'description' => 'This is a feature of location',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Product',
                'key' => 'product_feature',
                'module' => 'Product',
                'description' => 'This is a feature of product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Collection',
                'key' => 'collection_feature',
                'module' => 'Product',
                'description' => 'This is a feature of collection',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inventory',
                'key' => 'inventory_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of inventory',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Opening Stock',
                'key' => 'opening_stock_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of opening stock',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stock Transfer',
                'key' => 'stock_transfer_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of stock transfer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stock Ledger',
                'key' => 'stock_ledger_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of stock ledger',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stock Balance',
                'key' => 'stock_balance_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of stock balance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Purchase Order',
                'key' => 'purchase_order_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of purchase order',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Goods Receive Note',
                'key' => 'goods_receive_note_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of goods receive note',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Purchase Return',
                'key' => 'purchase_return_feature',
                'module' => 'Inventory',
                'description' => 'This is a feature of purchase return',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Delivery Provider',
                'key' => 'delivery_provider_feature',
                'module' => 'Sale',
                'description' => 'This is a feature of delivery provider',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Account',
                'key' => 'account_feature',
                'module' => 'Accounting',
                'description' => 'This is a feature of account',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashbook',
                'key' => 'cashbook_feature',
                'module' => 'Accounting',
                'description' => 'This is a feature of cashbook',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashbook Transaction',
                'key' => 'cashbook_transaction_feature',
                'module' => 'Accounting',
                'description' => 'This is a feature of cashbook transaction',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashbook Transfer',
                'key' => 'cashbook_transfer_feature',
                'module' => 'Accounting',
                'description' => 'This is a feature of cashbook transfer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashbook Adjustment',
                'key' => 'cashbook_adjustment_feature',
                'module' => 'Accounting',
                'description' => 'This is a feature of cashbook adjustment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashbook Ledger',
                'key' => 'cashbook_ledger_feature',
                'module' => 'Accounting',
                'description' => 'This is a feature of cashbook ledger',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }
    }
}
