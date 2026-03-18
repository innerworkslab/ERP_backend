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

            // Branch module
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
            // Department module
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
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }
    }
}
