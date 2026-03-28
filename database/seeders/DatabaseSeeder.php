<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\AccessControl\database\seeders\FeatureSeeder;
use Modules\AccessControl\database\seeders\PermissionSeeder;
use Modules\Location\database\seeders\CitySeeder;
use Modules\Location\database\seeders\StateSeeder;
use Modules\Organization\database\seeders\OrganizationDatabaseSeeder;
use Modules\PriceGroup\database\seeders\PriceGroupDatabaseSeeder;
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
            OrganizationDatabaseSeeder::class,
            PriceGroupDatabaseSeeder::class,
            StateSeeder::class,
            CitySeeder::class
        ]);
    }
}
