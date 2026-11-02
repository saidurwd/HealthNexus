<?php

namespace Tests\Feature\Ai;

use App\Models\Ai\AiModel;
use App\Models\Ai\AiUseCase;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\Ai\AiInstaller;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Ai\UseCaseService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Portal\PortalTestCase;

/** A hospital with the local engine evaluated, approved and switched on, a clinician who may use and review AI, and a patient with a few records. */
abstract class AiTestCase extends PortalTestCase
{
    protected User $clinician;

    protected function setUp(): void
    {
        parent::setUp();
        FakeProvider::script(null);
        $owner = $this->actor(['ai.usecase.manage']);
        $approver = $this->actor(['ai.usecase.approve', 'ai.model.approve']);
        app(AiInstaller::class)->startLocal($this->organization->id, $owner, $approver);
        $this->clinician = $this->actor(['ai.use', 'ai.output.review', 'patients.view']);
    }

    protected function tearDown(): void
    {
        FakeProvider::script(null);
        parent::tearDown();
    }

    /** Switch on a function above low risk the way a hospital would: an owner sets it up, a second person approves. */
    protected function enableFunction(string $code): AiUseCase
    {
        $owner = $this->actor(['ai.usecase.manage']);
        $approver = $this->actor(['ai.usecase.approve']);
        $uc = AiUseCase::query()->where('organization_id', $this->organization->id)->where('code', $code)->firstOrFail();
        $local = AiModel::query()->where('organization_id', $this->organization->id)->where('status', 'approved')->firstOrFail();
        $uc->update(['registered_by' => $owner->id]);
        app(UseCaseService::class)->setup($uc, $owner, $local->provider, $local);

        return app(UseCaseService::class)->enable($uc->refresh(), $approver);
    }

    /** A patient with an allergy, a diagnosis, a visit with vitals and a note that someone wrote. */
    protected function chart(string $note = 'Patient says the pain is worse at night.'): Patient
    {
        $patient = $this->patient(['first_name' => 'Rina', 'last_name' => 'Akter']);
        DB::table('patient_allergies')->insert(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'reaction' => 'anaphylaxis', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $doctor = $this->actor([]);
        DB::table('diagnoses')->insert(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'code' => 'E11', 'description' => 'Type 2 diabetes mellitus', 'status' => 'confirmed', 'recorded_at' => now()->subDays(20), 'created_at' => now(), 'updated_at' => now()]);
        $enc = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'started_at' => now()->subDays(3), 'notes' => $note, 'status' => 'completed']);
        DB::table('encounter_vitals')->insert(['encounter_id' => $enc->id, 'patient_id' => $patient->id, 'recorded_at' => now()->subDays(3), 'systolic' => 142, 'diastolic' => 90, 'pulse_rate' => 88, 'oxygen_saturation' => 97, 'created_at' => now(), 'updated_at' => now()]);

        return $patient;
    }
}
