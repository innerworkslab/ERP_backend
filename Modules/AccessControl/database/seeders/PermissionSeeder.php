<?php

namespace Modules\AccessControl\database\seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\app\Models\Feature;
use Modules\AccessControl\app\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            // Branch
            'branch_feature' => [
                ['name' => 'View Branch', 'key' => 'view_branch'],
                ['name' => 'Create Branch', 'key' => 'create_branch'],
                ['name' => 'Update Branch', 'key' => 'update_branch'],
                ['name' => 'Delete Branch', 'key' => 'delete_branch'],
            ],

            // Department
            'department_feature' => [
                ['name' => 'View Department', 'key' => 'view_department'],
                ['name' => 'Create Department', 'key' => 'create_department'],
                ['name' => 'Update Department', 'key' => 'update_department'],
                ['name' => 'Delete Department', 'key' => 'delete_department'],
            ],

            // Role
            'role_feature' => [
                ['name' => 'View Role', 'key' => 'view_role'],
                ['name' => 'Create Role', 'key' => 'create_role'],
                ['name' => 'Update Role', 'key' => 'update_role'],
                ['name' => 'Delete Role', 'key' => 'delete_role'],
            ],

            // Feature
            'feature_feature' => [
                ['name' => 'View Feature', 'key' => 'view_feature'],
                ['name' => 'Update Feature', 'key' => 'update_feature'],
                ['name' => 'Assign Role To Feature', 'key' => 'assign_role_to_feature'],
            ],
            // User
            'user_feature' => [
                ['name' => 'View User', 'key' => 'view_user'],
                ['name' => 'Create User', 'key' => 'create_user'],
                ['name' => 'Update User', 'key' => 'update_user'],
                ['name' => 'Delete User', 'key' => 'delete_user'],
                ['name' => 'Assign Role To User', 'key' => 'assign_role_to_user'],
            ],
            // Customer
            'customer_feature' => [
                ['name' => 'View Customer', 'key' => 'view_customer'],
                ['name' => 'Create Customer', 'key' => 'create_customer'],
                ['name' => 'Update Customer', 'key' => 'update_customer'],
                ['name' => 'Delete Customer', 'key' => 'delete_customer'],
            ],
            // Supplier
            'supplier_feature' => [
                ['name' => 'View Supplier', 'key' => 'view_supplier'],
                ['name' => 'Create Supplier', 'key' => 'create_supplier'],
                ['name' => 'Update Supplier', 'key' => 'update_supplier'],
                ['name' => 'Delete Supplier', 'key' => 'delete_supplier'],
            ],

            // Feature Recommendation Rule
            'feature_recommendation_rule_feature' => [
                ['name' => 'View Feature Recommendation Rule', 'key' => 'view_feature_recommendation_rule'],
                ['name' => 'Create Feature Recommendation Rule', 'key' => 'create_feature_recommendation_rule'],
                ['name' => 'Update Feature Recommendation Rule', 'key' => 'update_feature_recommendation_rule'],
                ['name' => 'Delete Feature Recommendation Rule', 'key' => 'delete_feature_recommendation_rule'],
            ],

            // Price Group
            'price_group_feature' => [
                ['name' => 'View Price Group', 'key' => 'view_price_group'],
                ['name' => 'Create Price Group', 'key' => 'create_price_group'],
                ['name' => 'Update Price Group', 'key' => 'update_price_group'],
                ['name' => 'Delete Price Group', 'key' => 'delete_price_group'],
            ],

            // Discount Group
            'discount_group_feature' => [
                ['name' => 'View Discount Group', 'key' => 'view_discount_group'],
                ['name' => 'Create Discount Group', 'key' => 'create_discount_group'],
                ['name' => 'Update Discount Group', 'key' => 'update_discount_group'],
                ['name' => 'Delete Discount Group', 'key' => 'delete_discount_group'],
            ],
            // Variation
            'variation_feature' => [
                ['name' => 'View Variation', 'key' => 'view_variation'],
                ['name' => 'Create Variation', 'key' => 'create_variation'],
                ['name' => 'Update Variation', 'key' => 'update_variation'],
                ['name' => 'Delete Variation', 'key' => 'delete_variation'],
            ],

            // UOM
            'uom_feature' => [
                ['name' => 'View UOM', 'key' => 'view_uom'],
                ['name' => 'Create UOM', 'key' => 'create_uom'],
                ['name' => 'Update UOM', 'key' => 'update_uom'],
                ['name' => 'Delete UOM', 'key' => 'delete_uom'],
            ],

            // UOM Conversion
            'uom_conversion_feature' => [
                ['name' => 'View UOM Conversion', 'key' => 'view_uom_conversion'],
                ['name' => 'Create UOM Conversion', 'key' => 'create_uom_conversion'],
                ['name' => 'Update UOM Conversion', 'key' => 'update_uom_conversion'],
                ['name' => 'Delete UOM Conversion', 'key' => 'delete_uom_conversion'],
            ],

            //Currency 
            'currency_feature' => [
                ['name' => 'View Currency', 'key' => 'view_currency'],
                ['name' => 'Create Currency', 'key' => 'create_currency'],
                ['name' => 'Update Currency', 'key' => 'update_currency'],
                ['name' => 'Delete Currency', 'key' => 'delete_currency'],
            ],
            
            // Brand
            'brand_feature' => [
                ['name' => 'View Brand', 'key' => 'view_brand'],
                ['name' => 'Create Brand', 'key' => 'create_brand'],
                ['name' => 'Update Brand', 'key' => 'update_brand'],
                ['name' => 'Delete Brand', 'key' => 'delete_brand'],
            ],

            //Category
            'category_feature' => [
                ['name' => 'View Category', 'key' => 'view_category'],
                ['name' => 'Create Category', 'key' => 'create_category'],
                ['name' => 'Update Category', 'key' => 'update_category'],
                ['name' => 'Delete Category', 'key' => 'delete_category'],
            ],

            // Tax
            'tax_feature' => [
                ['name' => 'View Tax', 'key' => 'view_tax'],
                ['name' => 'Create Tax', 'key' => 'create_tax'],
                ['name' => 'Update Tax', 'key' => 'update_tax'],
                ['name' => 'Delete Tax', 'key' => 'delete_tax'],
            ],

            //Product
            'product_feature' => [
                ['name' => 'View Product', 'key' => 'view_product'],
                ['name' => 'Create Product', 'key' => 'create_product'],
                ['name' => 'Update Product', 'key' => 'update_product'],
                ['name' => 'Delete Product', 'key' => 'delete_product'],
            ],

            // Collection
            'collection_feature' => [
                ['name' => 'View Collection', 'key' => 'view_collection'],
                ['name' => 'Create Collection', 'key' => 'create_collection'],
                ['name' => 'Update Collection', 'key' => 'update_collection'],
                ['name' => 'Delete Collection', 'key' => 'delete_collection'],
            ],

            // Inventory
            'inventory_feature' => [
                ['name' => 'View Inventory', 'key' => 'view_inventory'],
                ['name' => 'Create Inventory', 'key' => 'create_inventory'],
                ['name' => 'Update Inventory', 'key' => 'update_inventory'],
                ['name' => 'Delete Inventory', 'key' => 'delete_inventory'],
            ],
            
            // Opening Stock
            'opening_stock_feature' => [
                ['name' => 'View Opening Stock', 'key' => 'view_opening_stock'],
                ['name' => 'Create Opening Stock', 'key' => 'create_opening_stock'],
                ['name' => 'Update Opening Stock', 'key' => 'update_opening_stock'],
                ['name' => 'Delete Opening Stock', 'key' => 'delete_opening_stock'],
            ],

            // Stock Transfer
            'stock_transfer_feature' => [
                ['name' => 'View Stock Transfer', 'key' => 'view_stock_transfer'],
                ['name' => 'Create Stock Transfer', 'key' => 'create_stock_transfer'],
                ['name' => 'Update Stock Transfer', 'key' => 'update_stock_transfer'],
                ['name' => 'Delete Stock Transfer', 'key' => 'delete_stock_transfer'],
            ],
            
            // Location
            'location_feature' => [
                ['name' => 'View Location Record', 'key' => 'view_location_record'],
            ],
        ];

        foreach ($permissions as $key => $perms) {

            $feature = Feature::where('key', $key)->first();

            if (!$feature)
                continue;

            foreach ($perms as $perm) {
                Permission::updateOrCreate(
                    ['key' => $perm['key']],
                    [
                        'feature_id' => $feature->id,
                        'name' => $perm['name'],
                        'key' => $perm['key'],
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
