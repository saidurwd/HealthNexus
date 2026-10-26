<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SettingsTableSeeder::class,
            CodeSystemSeeder::class,
            MasterDataSeeder::class,
            OrganizationWithTwoBranchesSeeder::class,
            BillingSeeder::class,
            LabSeeder::class,
            RadiologySeeder::class,
            PharmacySeeder::class,
            IpdSeeder::class,
            NursingSeeder::class,
            OtSeeder::class,
            IcuSeeder::class,
            WorkflowSeeder::class,
            ClinicalMasterDataSeeder::class,
        ]);

        // Six months of history and test users for every module — opt in, it takes a few minutes (docs/demo-data.md).
        if (env('SEED_DEMO_HISTORY', false) && ! app()->isProduction()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
