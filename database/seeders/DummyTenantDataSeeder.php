<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
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

        $organizations = Organization::factory()
            ->count(4)
            ->create();

        foreach ($organizations as $organization) {
            $user->organizations()->attach($organization->id, ['access_level' => 'admin']);

            $branches = Branch::factory()
                ->count(3)
                ->for($organization)
                ->create();

            foreach ($branches as $branch) {
                $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);

                Department::factory()
                    ->count(2)
                    ->for($organization)
                    ->for($branch)
                    ->create();
            }
        }
    }
}
