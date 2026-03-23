<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\AccessControl\app\Models\Permission;
use Modules\AccessControl\database\seeders\FeatureSeeder;
use Modules\AccessControl\database\seeders\PermissionSeeder;
use Modules\Stakeholder\database\seeders\StakeholderDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            FeatureSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
            StakeholderDatabaseSeeder::class,
        ]);
    }
}
