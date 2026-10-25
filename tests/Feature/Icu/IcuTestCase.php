<?php

namespace Tests\Feature\Icu;

use App\Models\Encounter;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuEpisode;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Models\Patient;
use App\Services\Icu\IcuAdmissionRequestService;
use App\Services\Icu\IcuEpisodeService;
use App\Services\SettingsService;
use Tests\Feature\Ipd\IpdTestCase;

abstract class IcuTestCase extends IpdTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SettingsService::get() has no runtime fallback to defaults() when no row exists —
        // only SettingsTableSeeder populates those into the DB, which a RefreshDatabase test
        // never runs. Same gotcha hit in every prior phase's test case.
        app(SettingsService::class)->set('icu.admission_requires_approval', false);
        app(SettingsService::class)->set('icu.alert_threshold_rules', [
            'heart_rate' => ['low' => 50, 'high' => 130, 'severity' => 'high'],
            'spo2' => ['low' => 90, 'high' => null, 'severity' => 'high'],
        ]);
        app(SettingsService::class)->set('icu.scoring_definitions', [
            'gcs' => [
                'label' => 'Glasgow Coma Scale', 'version' => 1,
                'variables' => ['eye_opening' => [1, 4], 'verbal_response' => [1, 5], 'motor_response' => [1, 6]],
                'bands' => [
                    ['min' => 13, 'max' => 15, 'interpretation' => 'Mild'],
                    ['min' => 9, 'max' => 12, 'interpretation' => 'Moderate'],
                    ['min' => 3, 'max' => 8, 'interpretation' => 'Severe'],
                ],
            ],
        ]);
    }

    protected function makeIcuWard(): IpdWard
    {
        return IpdWard::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ICU-'.uniqid(), 'name' => 'Test ICU', 'ward_type' => IpdWard::WARD_TYPE_ICU,
            'capacity' => 4, 'isolation_capable' => true, 'is_active' => true,
        ]);
    }

    protected function makeIcuBed(?IpdWard $ward = null): IpdBed
    {
        $ward ??= $this->makeIcuWard();

        $room = IpdRoom::create([
            'company_id' => $this->company->id, 'branch_id' => null, 'ward_id' => $ward->id,
            'room_number' => (string) random_int(100, 999), 'room_type' => 'icu',
            'capacity' => 1, 'gender_policy' => 'any', 'isolation_capable' => true, 'is_active' => true,
        ]);

        return IpdBed::create([
            'company_id' => $this->company->id, 'branch_id' => null, 'room_id' => $room->id,
            'bed_code' => 'ICU-BED-'.uniqid(), 'gender_type' => 'any', 'status' => IpdBed::STATUS_AVAILABLE,
            'icu_capable' => true, 'ventilator_capable' => true, 'oxygen_available' => true,
            'monitor_available' => true, 'is_active' => true,
        ]);
    }

    protected function makeAdmissionRequest(?Patient $patient = null, bool $isEmergency = false): IcuAdmissionRequest
    {
        $patient ??= Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);

        return app(IcuAdmissionRequestService::class)->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id,
            'admission_reason' => 'Respiratory failure', 'priority' => 'urgent',
            'is_emergency' => $isEmergency, 'override_reason' => $isEmergency ? 'Cardiac arrest, immediate ICU need' : null,
        ], $this->user);
    }

    protected function makeEpisode(): IcuEpisode
    {
        $request = $this->makeAdmissionRequest();
        $bed = $this->makeIcuBed();

        return app(IcuEpisodeService::class)->admit($request->fresh(), ['bed_id' => $bed->id], $this->user);
    }
}
