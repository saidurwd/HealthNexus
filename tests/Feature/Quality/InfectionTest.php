<?php

namespace Tests\Feature\Quality;

use App\Models\Quality\QaOutbreak;
use App\Services\Quality\InfectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InfectionTest extends QualityTestCase
{
    private function svc(): InfectionService
    {
        return app(InfectionService::class);
    }

    public function test_a_case_is_suspected_then_confirmed_or_ruled_out_once(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $case = $this->svc()->reportCase($this->organization->id, ['patient_id' => $this->patient()->id, 'hai_type' => 'cauti', 'organism' => 'E. coli', 'device' => 'catheter', 'onset_on' => '2026-09-08', 'ward_id' => 5], $ic);
        $this->assertSame('suspected', $case->status);
        $this->assertSame('confirmed', $this->svc()->decide($case, $ic, true)->status);
        $this->expectException(ValidationException::class);
        $this->svc()->decide($case->fresh(), $ic, false);
    }

    public function test_the_hospital_day_decides_whether_it_counts_as_hospital_acquired(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $case = $this->svc()->reportCase($this->organization->id, ['patient_id' => $this->patient()->id, 'hai_type' => 'ssi', 'onset_on' => '2026-09-08'], $ic);
        $this->assertFalse($this->svc()->hospitalAcquired($case), 'no admission: day unknown');
        $case->update(['hospital_day' => 2]);
        $this->assertFalse($this->svc()->hospitalAcquired($case));
        $case->update(['hospital_day' => 3]);
        $this->assertTrue($this->svc()->hospitalAcquired($case));
    }

    public function test_bad_input_is_refused(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $p = $this->patient();
        foreach ([['hai_type' => 'flu'], ['onset_on' => '2026-09-20'], ['admission_id' => 999999], ['patient_id' => 999999]] as $bad) {
            try {
                $this->svc()->reportCase($this->organization->id, $bad + ['patient_id' => $p->id, 'hai_type' => 'mrsa', 'onset_on' => '2026-09-08'], $ic);
                $this->fail('accepted '.json_encode($bad));
            } catch (ValidationException) {
            }
        }
        $this->assertTrue(true);
    }

    public function test_isolation_is_a_flag_other_screens_can_ask_about(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $p = $this->patient();
        $i = $this->svc()->isolate($this->organization->id, $p, 'airborne', 'Suspected tuberculosis', $ic);
        $this->assertSame(['airborne'], $this->svc()->activePrecautions($this->organization->id, $p->id));
        try {
            $this->svc()->isolate($this->organization->id, $p, 'airborne', 'Again', $ic);
            $this->fail('twice');
        } catch (ValidationException) {
        }
        $this->svc()->endIsolation($i, $ic);
        $this->assertSame([], $this->svc()->activePrecautions($this->organization->id, $p->id));
    }

    public function test_hand_hygiene_is_a_percentage_and_cannot_exceed_what_was_observed(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $this->svc()->auditHandHygiene($this->organization->id, ['staff_role' => 'nurse', 'moments_observed' => 40, 'moments_compliant' => 30, 'audited_on' => '2026-09-05', 'ward_name' => 'Ward A'], $ic);
        $this->svc()->auditHandHygiene($this->organization->id, ['staff_role' => 'doctor', 'moments_observed' => 60, 'moments_compliant' => 50, 'audited_on' => '2026-09-06'], $ic);
        $this->assertSame(80.0, $this->svc()->handHygiene($this->organization->id, '2026-09-01', '2026-09-30'));
        $this->assertNull($this->svc()->handHygiene($this->organization->id, '2026-08-01', '2026-08-31'));
        $this->expectException(ValidationException::class);
        $this->svc()->auditHandHygiene($this->organization->id, ['staff_role' => 'nurse', 'moments_observed' => 10, 'moments_compliant' => 11], $ic);
    }

    public function test_three_cases_of_one_type_on_one_ward_open_an_outbreak_once(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $mk = fn (string $type, int $ward, string $day) => $this->svc()->reportCase($this->organization->id, ['patient_id' => $this->patient()->id, 'hai_type' => $type, 'ward_id' => $ward, 'onset_on' => $day], $ic);
        $mk('mrsa', 7, '2026-09-01');
        $mk('mrsa', 7, '2026-09-03');
        $mk('mrsa', 8, '2026-09-04'); // another ward
        $this->assertSame(0, QaOutbreak::query()->count());
        $mk('cdiff', 7, '2026-09-05'); // another type
        $this->assertSame(0, QaOutbreak::query()->count());
        $mk('mrsa', 7, '2026-09-06');
        $this->assertSame(1, QaOutbreak::query()->count());
        $o = QaOutbreak::query()->first();
        $this->assertSame([7, 'mrsa', 3], [$o->ward_id, $o->hai_type, $o->case_count]);
        $this->assertNotNull(DB::table('clinical_alerts')->where('category', 'quality')->where('severity', 'critical')->first());
        $mk('mrsa', 7, '2026-09-07');
        $this->assertSame(1, QaOutbreak::query()->count(), 'another case joins the open outbreak');
        $this->assertSame(4, $o->fresh()->case_count);
        $this->svc()->closeOutbreak($o->fresh(), $ic, 'Ward cleaned and screened, no new cases for 14 days');
        $this->assertSame('closed', $o->fresh()->status);
    }

    public function test_ruled_out_cases_do_not_count_towards_an_outbreak(): void
    {
        $ic = $this->actor(['quality.infection.manage']);
        $cases = [];
        foreach (['2026-09-01', '2026-09-02'] as $d) {
            $cases[] = $this->svc()->reportCase($this->organization->id, ['patient_id' => $this->patient()->id, 'hai_type' => 'vap', 'ward_id' => 3, 'onset_on' => $d], $ic);
        }
        $this->svc()->decide($cases[0], $ic, false);
        $this->svc()->reportCase($this->organization->id, ['patient_id' => $this->patient()->id, 'hai_type' => 'vap', 'ward_id' => 3, 'onset_on' => '2026-09-03'], $ic);
        $this->assertSame(0, QaOutbreak::query()->count());
    }
}
