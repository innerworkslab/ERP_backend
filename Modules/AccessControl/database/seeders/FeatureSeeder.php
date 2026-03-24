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
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }
    }
}
