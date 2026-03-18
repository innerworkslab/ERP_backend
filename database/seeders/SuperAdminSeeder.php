<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AccessControl\app\Models\Feature;
use Modules\AccessControl\app\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            // 'is_super' => true,
            'branch_id' => null,
            'department_id' => null,
            'status' => 'active'
        ]);

        $features = Feature::pluck('id')->toArray();

        $role->features()->sync($features);

        $user = User::create([
            'name' => 'Super Admin',
            'phone_number' => '09123456789',
            'email'=> 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'branch_id' => null,
            'department_id' => null,
            'salary' => null,
            'sale_incentive' => null,
            'status' => 'active'
        ]);

        $user->features()->sync($features);
    }
}
