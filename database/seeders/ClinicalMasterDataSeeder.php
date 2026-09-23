<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\EncounterType;
use Illuminate\Database\Seeder;

/**
 * encounter_types is company-scoped (unlike the rest of MasterDataSeeder's system-wide tables),
 * so it has to run after companies exist — this seeder runs after CompanyWithTwoBranchesSeeder
 * rather than folding into MasterDataSeeder.
 */
class ClinicalMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            $types = [
                ['name' => 'New Consultation', 'code' => 'new_consultation'],
                ['name' => 'Follow-up', 'code' => 'follow_up'],
                ['name' => 'Review', 'code' => 'review'],
                ['name' => 'Second Opinion', 'code' => 'second_opinion'],
                ['name' => 'Procedure', 'code' => 'procedure'],
                ['name' => 'Health Checkup', 'code' => 'health_checkup'],
                ['name' => 'Referral', 'code' => 'referral'],
                ['name' => 'Telemedicine', 'code' => 'telemedicine'],
                ['name' => 'Walk-in', 'code' => 'walk_in'],
                ['name' => 'Other', 'code' => 'other'],
            ];

            foreach ($types as $type) {
                EncounterType::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $type['name']],
                    ['code' => $type['code'], 'is_active' => true],
                );
            }
        });
    }
}
