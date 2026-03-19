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
