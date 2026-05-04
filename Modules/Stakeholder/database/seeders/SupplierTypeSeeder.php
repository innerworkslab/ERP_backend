<?php

namespace Modules\Stakeholder\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Stakeholder\app\Models\SupplierType;

class SupplierTypeSeeder extends Seeder
{
    public function run(): void
    {
        SupplierType::create([
            'name' => 'Retail',
        ]);
    }
}
