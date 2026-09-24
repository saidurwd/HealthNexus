<?php

namespace Tests\Feature\Nursing;

use App\Models\Nursing\NursingMedicationAdministration;
use App\Services\Nursing\MedicationAdministrationService;
use Illuminate\Validation\ValidationException;

class MarWorkflowTest extends NursingTestCase
{
    public function test_episode_starts_automatically_when_a_patient_is_admitted(): void
    {
        $admission = $this->makeAdmission();
        event(new \App\Events\Ipd\PatientAdmitted($admission));

        $this->assertDatabaseHas('nursing_episodes', ['admission_id' => $admission->id, 'status' => 'active']);
    }

    public function test_administer_records_status_nurse_and_checks(): void
    {
        $mar = $this->makeMar($this->makeEpisode());

        $updated = app(MedicationAdministrationService::class)->administer($mar, $this->user, ['patient' => true, 'dose' => true]);

        $this->assertSame('administered', $updated->status);
        $this->assertSame($this->user->id, $updated->administered_by);
        $this->assertNotNull($updated->administered_at);
    }

    public function test_second_administration_of_same_item_is_rejected(): void
    {
        $mar = $this->makeMar($this->makeEpisode());
        $service = app(MedicationAdministrationService::class);
        $service->administer($mar, $this->user, ['patient' => true]);

        $this->expectException(ValidationException::class);
        $service->administer($mar->fresh(), $this->user, ['patient' => true]);
    }

    public function test_hold_refuse_omit_never_mark_as_administered_and_lock_the_row(): void
    {
        $service = app(MedicationAdministrationService::class);
        $episode = $this->makeEpisode();

        foreach (['hold' => 'held', 'refuse' => 'refused', 'omit' => 'omitted'] as $method => $status) {
            $mar = $this->makeMar($episode);
            $updated = $service->{$method}($mar, $this->user, 'reason');
            $this->assertSame($status, $updated->status);
            $this->assertNull($updated->administered_at);

            try {
                $service->administer($updated, $this->user, ['patient' => true]);
                $this->fail('A finalized item must not be administrable.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_controlled_medication_requires_a_witness(): void
    {
        $mar = $this->makeMar($this->makeEpisode(), controlled: true);
        $service = app(MedicationAdministrationService::class);

        try {
            $service->administer($mar, $this->user, ['patient' => true]);
            $this->fail('Witness should be required.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('witness', $e->errors());
        }

        $witness = $this->makeNurse();
        $updated = $service->administer($mar->fresh(), $this->user, ['patient' => true], [], $witness);
        $this->assertSame($witness->id, $updated->witnessed_by);
    }

    public function test_correction_never_mutates_the_original_and_requires_a_finalized_row(): void
    {
        $service = app(MedicationAdministrationService::class);
        $mar = $this->makeMar($this->makeEpisode());

        try {
            $service->correct($mar, [], 'typo', $this->user);
            $this->fail('An un-finalized administration cannot be corrected.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $done = $service->administer($mar, $this->user, ['patient' => true]);
        $correction = $service->correct($done, [], 'wrong site recorded', $this->user);

        $this->assertSame('500', $done->fresh()->dose);
        $this->assertSame($correction->id, $done->fresh()->superseded_by_correction_id);
        $this->assertSame('administered', $done->fresh()->status);
    }

    public function test_dose_and_route_must_be_confirmed(): void
    {
        $mar = $this->makeMar($this->makeEpisode());
        $mar->update(['dose' => null]);

        $this->expectException(ValidationException::class);
        app(MedicationAdministrationService::class)->administer($mar, $this->user, ['patient' => true]);
    }
}
