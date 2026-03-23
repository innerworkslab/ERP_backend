<?php

namespace Modules\Stakeholder\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Stakeholder\app\Models\CustomerType;

class CustomerTypeSeeder extends Seeder
{
    public function run(): void
    {
        CustomerType::create([
            'name' => 'Retail'
            ]);
    }
}
