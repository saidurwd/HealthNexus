<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\EncounterType;
use Illuminate\Database\Seeder;

/**
 * encounter_types is organization-scoped (unlike the rest of MasterDataSeeder's system-wide tables),
 * so it has to run after organizations exist — this seeder runs after OrganizationWithTwoBranchesSeeder
 * rather than folding into MasterDataSeeder.
 */
class ClinicalMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(function (Organization $organization) {
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
                    ['organization_id' => $organization->id, 'name' => $type['name']],
                    ['code' => $type['code'], 'is_active' => true],
                );
            }
        });
    }
}
