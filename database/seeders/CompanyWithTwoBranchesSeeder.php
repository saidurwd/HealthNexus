<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CompanyWithTwoBranchesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::create([
            'name' => 'HealthNexus Hospital Group',
            'code' => 'HN-HG',
            'slug' => 'healthnexus-hospital-group',
            'email' => 'info@healthnexus.test',
            'phone' => '+1-555-0100',
            'address' => '123 Healthcare Blvd, Medical District',
            'is_active' => true,
        ]);

        $branch1 = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Hospital',
            'code' => 'MAIN',
            'slug' => 'main-hospital',
            'email' => 'main@healthnexus.test',
            'phone' => '+1-555-0101',
            'address' => '123 Healthcare Blvd, Medical District',
            'is_active' => true,
        ]);

        $branch2 = Branch::create([
            'company_id' => $company->id,
            'name' => 'City Center Clinic',
            'code' => 'CITY',
            'slug' => 'city-center-clinic',
            'email' => 'city@healthnexus.test',
            'phone' => '+1-555-0102',
            'address' => '456 Downtown Ave, City Center',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@healthnexus.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->companies()->attach($company->id, ['access_level' => 'admin', 'is_default' => true]);
        $user->branches()->attach($branch1->id, ['access_level' => 'manager', 'company_id' => $company->id, 'is_default' => true]);
        $user->branches()->attach($branch2->id, ['access_level' => 'manager', 'company_id' => $company->id]);

        $allPermissions = Permission::all();
        $user->givePermissionTo($allPermissions);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($allPermissions);
        $user->assignRole('super_admin');
    }
}
