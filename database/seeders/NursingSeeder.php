<?php

namespace Database\Seeders;

use App\Models\Nursing\NursingShift;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Default Morning/Evening/Night shifts per organization — fully editable configuration, never assumed
 * to match every hospital's schedule (spec §9).
 */
class NursingSeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(function (Organization $organization) {
            foreach ([
                ['Morning', '07:00', '15:00'],
                ['Evening', '15:00', '23:00'],
                ['Night', '23:00', '07:00'],
            ] as [$name, $start, $end]) {
                NursingShift::firstOrCreate(
                    ['organization_id' => $organization->id, 'branch_id' => null, 'name' => $name],
                    ['start_time' => $start, 'end_time' => $end, 'grace_period_minutes' => 15, 'is_active' => true],
                );
            }
        });
    }
}
