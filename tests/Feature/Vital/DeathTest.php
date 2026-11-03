<?php

namespace Tests\Feature\Vital;

use App\Models\Patient;
use App\Models\Vital\VrDeathCertification;
use App\Services\Vital\DeathService;
use App\Services\Vital\MedicoLegalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeathTest extends VitalTestCase
{
    private function service(): DeathService
    {
        return app(DeathService::class);
    }

    private function refused(callable $fn, string $field): void
    {
        try {
            $fn();
            $this->fail("expected [$field] to be refused");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    public function test_deaths_with_no_certificate_are_listed_from_the_patient_record_and_the_mortuary_once_each(): void
    {
        $a = $this->patient(['deceased_at' => now()->subDay()]);
        $b = $this->patient();
        $this->mortuaryCase($b);
        $c = $this->patient(['deceased_at' => now()->subDays(2)]);
        $this->mortuaryCase($c);
        $covered = $this->patient(['deceased_at' => now()->subDays(3)]);
        $this->service()->draft($covered, $this->death(), $this->actor());
        $other = Patient::factory()->create(['deceased_at' => now()->subDay()]);

        $ids = $this->service()->awaiting($this->organization->id)->pluck('patient_id')->all();

        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $ids, 'one row each; a certificate covers a death; another hospital is not listed');
        $this->assertNotContains($other->id, $ids);
    }

    public function test_the_cause_of_death_is_a_chain_and_the_direct_cause_is_needed(): void
    {
        $p = $this->patient();
        $this->refused(fn () => $this->service()->draft($p, $this->death(['cause_a' => '']), $this->actor()), 'cause_a');
        $this->refused(fn () => $this->service()->draft($p, $this->death(['cause_b' => '', 'cause_c' => 'Diabetes']), $this->actor()), 'cause_c');
        $this->refused(fn () => $this->service()->draft($p, $this->death(['manner' => 'sleep']), $this->actor()), 'manner');
        $this->refused(fn () => $this->service()->draft($p, $this->death(['died_at' => now()->addDay()->toDateTimeString()]), $this->actor()), 'died_at');
        $this->assertSame(0, VrDeathCertification::count());
    }

    public function test_one_live_certificate_per_patient_and_a_cancelled_one_can_be_written_again(): void
    {
        $p = $this->patient();
        $first = $this->service()->draft($p, $this->death(), $this->actor());
        $this->refused(fn () => $this->service()->draft($p, $this->death(), $this->actor()), 'patient_id');

        $this->service()->cancel($first, 'Wrong patient chart was used.', $this->actor());
        $second = $this->service()->draft($p, $this->death(), $this->actor());

        $this->assertSame(['cancelled', 'draft'], [$first->refresh()->status, $second->status]);
        $this->assertMatchesRegularExpression('/^DC-\d{4}-00002$/', $second->number);
    }

    public function test_certifying_signs_the_cause_stamps_the_doctor_and_records_the_death_on_the_patient(): void
    {
        $p = $this->patient();
        $doctor = $this->actor();
        $c = $this->service()->draft($p, $this->death(), $this->actor());

        $c = $this->service()->certify($c, $doctor, ' BMDC-12345 ');

        $this->assertSame(['certified', $doctor->id, $doctor->name, 'BMDC-12345'], [$c->status, $c->certifier_id, $c->certifier_name, $c->certifier_reg_no]);
        $this->assertNotNull($p->refresh()->deceased_at);
        $this->refused(fn () => $this->service()->certify($c->refresh(), $doctor), 'status');
    }

    public function test_a_natural_death_is_not_a_police_matter_and_an_accident_opens_a_police_case(): void
    {
        $natural = $this->service()->draft($this->patient(), $this->death(), $this->actor());
        $accident = $this->service()->draft($this->patient(), $this->death(['manner' => 'accident']), $this->actor());

        $this->assertFalse($natural->medico_legal);
        $this->assertNull($natural->medicolegal_case_id);
        $this->assertTrue($accident->medico_legal);
        $this->assertNotNull($accident->medicolegal_case_id);
        $this->assertSame(1, DB::table('vr_medicolegal_cases')->count());
    }

    public function test_the_certificate_goes_to_the_family_only_after_a_second_person_checks_who_they_are(): void
    {
        $doctor = $this->actor();
        $c = $this->service()->certify($this->service()->draft($this->patient(), $this->death(), $this->actor()), $doctor);
        $issue = fn ($by, $o = []) => $this->service()->issue($c->refresh(), $by, $o['to'] ?? 'Rahim, son', $o['rel'] ?? 'Son', $o['type'] ?? 'nid', $o['number'] ?? '1990123456789');

        $this->refused(fn () => $issue($doctor), 'status');
        $this->refused(fn () => $issue($this->actor(), ['number' => '']), 'issued_to');
        $this->refused(fn () => $issue($this->actor(), ['type' => 'library_card']), 'issued_to');
        $clerk = $this->actor();
        $issued = $issue($clerk);

        $this->assertSame(['issued', $clerk->id, 'Rahim, son'], [$issued->status, $issued->verified_by, $issued->issued_to]);
        $this->assertNotSame('1990123456789', DB::table('vr_death_certifications')->where('id', $c->id)->value('issued_id_number'), 'the identity number is stored encrypted');
        $this->assertSame('1990123456789', $issued->refresh()->issued_id_number);
    }

    public function test_a_certificate_held_by_a_police_case_is_not_issued_until_the_case_is_closed(): void
    {
        $p = $this->patient();
        $police = $this->actor();
        $c = $this->service()->certify($this->service()->draft($p, $this->death(['manner' => 'homicide']), $this->actor()), $this->actor());
        $issue = fn () => $this->service()->issue($c->refresh(), $this->actor(), 'Brother', 'Brother', 'nid', '1990123456789');

        $this->refused($issue, 'status');
        $case = $c->refresh()->medicolegalCase;
        app(MedicoLegalService::class)->informPolice($case, ['police_station' => 'Savar', 'gd_number' => 'GD-1'], $police);
        $this->refused($issue, 'status');
        app(MedicoLegalService::class)->close($case->refresh(), 'The police have what they need.', $police);

        $this->assertSame('issued', $issue()->status);
    }

    public function test_an_issued_certificate_is_cancelled_with_a_reason_never_edited(): void
    {
        $c = $this->service()->certify($this->service()->draft($this->patient(), $this->death(), $this->actor()), $this->actor());
        $c = $this->service()->issue($c, $this->actor(), 'Son', 'Son', 'nid', '1990123456789');

        $this->refused(fn () => $this->service()->cancel($c, 'x', $this->actor()), 'cancel_reason');
        $this->service()->cancel($c, 'Issued to the wrong person.', $this->actor());

        $this->assertSame('cancelled', $c->refresh()->status);
        $this->assertSame(1, $this->service()->awaiting($this->organization->id)->count(), 'the patient is listed as waiting for a certificate again');
    }
}
