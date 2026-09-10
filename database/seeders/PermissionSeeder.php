<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage companies',
            'manage branches',
            'manage departments',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $user = User::first();
        if ($user) {
            $user->assignRole($role);
        }
    }
}
