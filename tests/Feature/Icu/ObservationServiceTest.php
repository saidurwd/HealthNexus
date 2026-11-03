<?php

namespace Tests\Feature\Icu;

use App\Events\Clinical\ObservationRecorded;
use App\Models\Clinical\ClinicalObservation;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Clinical\Observations\ObservationData;
use App\Services\Clinical\Observations\ObservationReader;
use App\Services\Clinical\Observations\ObservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

/** H-03 step 1: shared store + read-through over OPD vitals and ICU observations. */
class ObservationServiceTest extends IcuTestCase
{
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function data(array $override = []): ObservationData
    {
        return new ObservationData(...array_merge([
            'organizationId' => $this->organization->id, 'patientId' => $this->patient->id, 'contextType' => 'opd',
            'key' => 'heart_rate', 'valueNumber' => 88.0,
        ], $override));
    }

    public function test_records_a_loinc_coded_observation_and_announces_it_with_ids_only(): void
    {
        Event::fake([ObservationRecorded::class]);

        $obs = app(ObservationService::class)->record($this->data(), $this->user);

        $this->assertSame('8867-4', $obs->code);
        $this->assertSame('/min', $obs->unit_code);
        $this->assertSame('Heart rate', $obs->display);
        $this->assertSame(26, strlen($obs->public_id));
        Event::assertDispatched(ObservationRecorded::class, fn ($e) => $e->observationId === $obs->id && $e->code === '8867-4');
    }

    public function test_implausible_values_wrong_units_and_unknown_contexts_are_rejected(): void
    {
        $service = app(ObservationService::class);

        foreach ([
            ['valueNumber' => 900.0],                       // heart rate 900/min
            ['valueNumber' => 80.0, 'unitCode' => 'mg'],    // wrong unit for the code
            ['contextType' => 'cafeteria'],
            ['key' => 'made_up'],
            ['valueNumber' => null],
        ] as $bad) {
            try {
                $service->record($this->data($bad));
                $this->fail('Expected a validation error for '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(0, ClinicalObservation::count());
    }

    public function test_a_patient_from_another_organization_is_refused(): void
    {
        $foreign = Patient::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->expectException(ValidationException::class);

        app(ObservationService::class)->record($this->data(['patientId' => $foreign->id]));
    }

    public function test_blood_pressure_panel_stores_both_members_in_one_set(): void
    {
        $result = app(ObservationService::class)->recordPanel('blood_pressure', ['systolic_bp' => 120, 'diastolic_bp' => 80], $this->data(['key' => null, 'valueNumber' => null]), $this->user);

        $this->assertSame('85354-9', $result['set']->panel_code);
        $this->assertEqualsCanonicalizing(['8480-6', '8462-4'], collect($result['observations'])->pluck('code')->all());
        $this->assertSame(2, $result['set']->observations()->count());
    }

    public function test_amend_supersedes_without_editing_and_entered_in_error_hides_the_value(): void
    {
        $service = app(ObservationService::class);
        $reader = app(ObservationReader::class);
        $first = $service->record($this->data(['valueNumber' => 70.0]));

        $second = $service->amend($first, 78, 'transcription error', $this->user);

        $this->assertSame(70.0, $first->fresh()->value_number, 'the original row is never edited');
        $this->assertSame($first->id, $second->supersedes_id);
        $this->assertSame(78.0, $reader->latest($this->organization->id, $this->patient->id, 'heart_rate')->value);

        $service->markEnteredInError($second, 'wrong patient');
        $this->assertNull($reader->latest($this->organization->id, $this->patient->id, 'heart_rate'));
    }

    public function test_one_trend_query_shows_opd_icu_and_new_store_values(): void
    {
        $episode = $this->makeEpisode();
        $patientId = $episode->patient_id;
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'patient_id' => $patientId]);

        DB::table('encounter_vitals')->insert([
            'encounter_id' => $encounter->id, 'patient_id' => $patientId, 'recorded_at' => '2026-10-20 08:00:00',
            'pulse_rate' => 80, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('icu_observations')->insert([
            'organization_id' => $this->organization->id, 'episode_id' => $episode->id, 'patient_id' => $patientId,
            'encounter_id' => $episode->encounter_id, 'observation_type' => 'heart_rate', 'value_numeric' => 112,
            'unit' => '/min', 'observed_at' => '2026-10-21 08:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(ObservationService::class)->record($this->data(['patientId' => $patientId, 'valueNumber' => 95.0, 'effectiveAt' => now()->setDate(2026, 10, 22)]));

        $series = app(ObservationReader::class)->series($this->organization->id, $patientId, 'heart_rate');

        $this->assertSame(['encounter_vitals', 'icu_observations', 'observation'], $series->pluck('source_module')->all());
        $this->assertSame([80.0, 112.0, 95.0], $series->pluck('value')->all());
    }

    public function test_reader_never_returns_another_organizations_values(): void
    {
        app(ObservationService::class)->record($this->data());
        $other = Organization::factory()->create();

        $this->assertCount(0, app(ObservationReader::class)->series($other->id, $this->patient->id, 'heart_rate'));
    }
}
