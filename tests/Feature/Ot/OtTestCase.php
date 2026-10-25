<?php

namespace Tests\Feature\Ot;

use App\Models\Encounter;
use App\Models\Ot\OtComplex;
use App\Models\Ot\OtProcedure;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtTheatre;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\Ot\OtPreoperativeChecklistService;
use App\Services\Ot\OtReceptionService;
use App\Services\Ot\OtSchedulingService;
use App\Services\Ot\OtSurgeryService;
use App\Services\Ot\OtSurgicalSafetyChecklistService;
use App\Services\SettingsService;
use Tests\Feature\Ipd\IpdTestCase;

abstract class OtTestCase extends IpdTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mirrors production defaults (SettingsService::defaults()) — SettingsService::get()
        // has no runtime fallback to defaults() when no row exists, so a fresh RefreshDatabase
        // test DB needs these seeded explicitly, same as Nursing's threshold-setting tests do.
        app(SettingsService::class)->set('ot.preop_checklist_items', [
            ['category' => 'patient_identity', 'label' => 'Patient identity confirmed', 'is_required' => true],
            ['category' => 'procedure', 'label' => 'Procedure confirmed', 'is_required' => true],
            ['category' => 'consent', 'label' => 'Consent obtained', 'is_required' => true],
            ['category' => 'fasting', 'label' => 'Fasting status confirmed', 'is_required' => false],
        ]);
        app(SettingsService::class)->set('ot.safety_checklist_items', [
            'sign_in' => ['Patient identity, site, procedure and consent confirmed', 'Pulse oximeter functioning'],
            'time_out' => ['Team introduced', 'Patient, site, procedure verbally confirmed'],
            'sign_out' => ['Instrument/swab/needle counts correct', 'Specimen labelling confirmed'],
        ]);
    }

    protected function makeTheatre(string $code = 'OT-TEST'): OtTheatre
    {
        $complex = OtComplex::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'COMPLEX-'.uniqid(), 'name' => 'Test Complex', 'is_active' => true,
        ]);

        return OtTheatre::create([
            'company_id' => $this->company->id, 'branch_id' => null, 'complex_id' => $complex->id,
            'theatre_code' => $code.'-'.uniqid(), 'name' => 'Test Theatre', 'capacity' => 1,
            'status' => OtTheatre::STATUS_AVAILABLE, 'is_active' => true,
        ]);
    }

    protected function makeProcedure(): OtProcedure
    {
        return OtProcedure::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'PROC-'.uniqid(), 'name' => 'Test Procedure', 'default_duration_minutes' => 60,
            'laterality_applicable' => true, 'site_applicable' => true, 'is_active' => true,
        ]);
    }

    protected function makeSurgeon(string $type = 'doctor'): Provider
    {
        return Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'provider_code' => 'SURG-'.uniqid(), 'name' => 'Dr. Surgeon', 'provider_type' => $type, 'status' => 'active',
        ]);
    }

    protected function makeSurgery(?Patient $patient = null, bool $withAdmission = false): OtSurgery
    {
        $patient ??= Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);

        $admissionId = null;

        if ($withAdmission) {
            $admission = \App\Models\Ipd\IpdAdmission::create([
                'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
                'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
                'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'transfer_requested',
            ]);
            $admissionId = $admission->id;
        }

        $procedure = $this->makeProcedure();

        return app(OtSurgeryService::class)->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'admission_id' => $admissionId,
            'primary_procedure_id' => $procedure->id, 'surgery_type' => 'elective',
        ], $this->user);
    }

    protected function makeDaySurgery(): OtSurgery
    {
        return $this->makeSurgery(withAdmission: false);
    }

    /**
     * Runs a surgery through the full schedule -> sign in -> start -> sign out -> complete
     * sequence, for tests whose focus is downstream of procedure completion (recovery/transfer).
     */
    protected function progressToCompleted(OtSurgery $surgery): OtSurgery
    {
        $theatre = $this->makeTheatre();

        app(OtSchedulingService::class)->schedule($surgery, [
            'theatre_id' => $theatre->id, 'scheduled_start_at' => now(), 'scheduled_end_at' => now()->addHours(2),
        ], $this->user);

        $reception = app(OtReceptionService::class);
        $safety = app(OtSurgicalSafetyChecklistService::class);

        $surgery = $reception->receive($surgery->fresh(), $this->user);

        $signIn = $safety->initializePhase($surgery, OtSurgicalSafetyChecklist::PHASE_SIGN_IN);
        foreach ($signIn->items as $item) {
            $safety->completeItem($item, $this->user, 'confirmed');
        }
        $safety->completePhase($signIn, $this->user);

        $surgery = $reception->start($surgery->fresh(), $this->user);

        $signOut = $safety->initializePhase($surgery, OtSurgicalSafetyChecklist::PHASE_SIGN_OUT);
        foreach ($signOut->items as $item) {
            $safety->completeItem($item, $this->user, 'confirmed');
        }
        $safety->completePhase($signOut, $this->user);

        return $reception->complete($surgery->fresh(), $this->user);
    }
}
