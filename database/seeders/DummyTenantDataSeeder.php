<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyTenantDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            return;
        }

        $companies = Company::factory()
            ->count(4)
            ->create();

        foreach ($companies as $company) {
            $user->companies()->attach($company->id, ['access_level' => 'admin']);

            $branches = Branch::factory()
                ->count(3)
                ->for($company)
                ->create();

            foreach ($branches as $branch) {
                $user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $company->id]);

                Department::factory()
                    ->count(2)
                    ->for($company)
                    ->for($branch)
                    ->create();
            }
        }
    }
}
