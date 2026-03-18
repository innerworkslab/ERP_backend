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

            // Branch 
            [
                'name' => 'View Branch',
                'key' => 'view_branch',
                'module' => 'Organization',
                'description' => 'View branch list'
            ],
            [
                'name' => 'Create Branch',
                'key' => 'create_branch',
                'module' => 'Organization',
                'description' => 'Create new branch'
            ],
            [
                'name' => 'Update Branch',
                'key' => 'update_branch',
                'module' => 'Organization',
                'description' => 'Update branch'
            ],
            [
                'name' => 'Delete Branch',
                'key' => 'delete_branch',
                'module' => 'Organization',
                'description' => 'Delete branch'
            ],
            // Department 
            [
                'name' => 'View Department',
                'key' => 'view_department',
                'module' => 'Organization',
                'description' => 'View department list'
            ],
            [
                'name' => 'Create Department',
                'key' => 'create_department',
                'module' => 'Organization',
                'description' => 'Create new department'
            ],
            [
                'name' => 'Update Department',
                'key' => 'update_department',
                'module' => 'Organization',
                'description' => 'Update department'
            ],
            [
                'name' => 'Delete Department',
                'key' => 'delete_department',
                'module' => 'Organization',
                'description' => 'Delete department'
            ],
            // Role 
            [
                'name' => 'View Role',
                'key' => 'view_role',
                'module' => 'Organization',
                'description' => 'View role list'
            ],
            [
                'name' => 'Create Role',
                'key' => 'create_role',
                'module' => 'Organization',
                'description' => 'Create new role'
            ],
            [
                'name' => 'Update Role',
                'key' => 'update_role',
                'module' => 'Organization',
                'description' => 'Update role'
            ],
            [
                'name' => 'Delete Role',
                'key' => 'delete_role',
                'module' => 'Organization',
                'description' => 'Delete role'
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
