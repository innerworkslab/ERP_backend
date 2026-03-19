<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\AccessControl\app\Models\Feature;
use Modules\AccessControl\app\Models\Permission;
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
        $permissions = Permission::pluck('id')->toArray();

        $role->features()->sync($features);

        $user = User::create([
            'name' => 'Super Admin',
            'phone_number' => '09123456789',
            'email'=> 'superadmin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'branch_id' => null,
            'department_id' => null,
            'status' => 'active'
        ]);

        $user->permissions()->sync($permissions);
    }
}
