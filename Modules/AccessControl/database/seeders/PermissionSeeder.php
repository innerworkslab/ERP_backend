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
