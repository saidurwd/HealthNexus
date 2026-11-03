<?php

namespace Tests\Feature\Watch;

use App\Models\Patient;
use App\Models\User;
use App\Models\Watch\SvCondition;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Vital\VitalTestCase;

/** A hospital whose "now" is Thursday 12 Nov 2026, 10:00 in Dhaka, with a way to write diagnoses. */
abstract class WatchTestCase extends VitalTestCase
{
    protected CarbonImmutable $now;

    protected User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = CarbonImmutable::parse('2026-11-12 04:00:00', 'UTC');
        Carbon::setTestNow($this->now);
        $this->doctor = $this->actor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function condition(array $o = []): SvCondition
    {
        return SvCondition::factory()->create($o + ['organization_id' => $this->organization->id, 'code' => 'dengue', 'icd_prefixes' => ['A90', 'A91'], 'window_days' => 7, 'min_cases' => 3, 'spike_factor' => 2.0, 'cluster_cases' => 3]);
    }

    /** A diagnosis with this code for a patient, written `$daysAgo` days before now (a new patient unless one is given). */
    protected function diagnose(string $code, int $daysAgo = 0, ?Patient $patient = null, string $status = 'confirmed'): Patient
    {
        $patient ??= $this->patient();
        DB::table('diagnoses')->insert(['organization_id' => $patient->organization_id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'code' => $code, 'coding_system' => 'ICD-10', 'description' => 'x',
            'status' => $status, 'is_primary' => true, 'recorded_at' => $this->now->subDays($daysAgo)->toDateTimeString(), 'created_at' => $this->now, 'updated_at' => $this->now]);

        return $patient;
    }

    protected function live(int $districtId, ?Patient $patient = null): Patient
    {
        $patient ??= $this->patient();
        DB::table('patient_addresses')->insert(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'address_type' => 'permanent', 'district_id' => $districtId, 'is_primary' => true, 'created_at' => $this->now, 'updated_at' => $this->now]);

        return $patient;
    }
}
