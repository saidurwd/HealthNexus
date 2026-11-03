<?php

namespace Tests\Feature\Vital;

use App\Models\Geo\GeoDistrict;
use App\Models\Vital\VrBirthNotification;
use App\Services\Geo\GeoInstaller;
use App\Services\Vital\BirthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BirthTest extends VitalTestCase
{
    private function service(): BirthService
    {
        return app(BirthService::class);
    }

    public function test_a_birth_is_written_down_with_a_number_and_waits_for_a_second_person(): void
    {
        $clerk = $this->actor();

        $n = $this->service()->draft($this->mother(), $this->birth(), $clerk);

        $this->assertMatchesRegularExpression('/^BN-\d{4}-00001$/', $n->number);
        $this->assertSame(['draft', 'live_birth', $clerk->id], [$n->status, $n->outcome, $n->created_by]);
        $this->assertStringEndsWith('00002', $this->service()->draft($this->mother(), $this->birth(), $clerk)->number, 'numbers run on');
    }

    public function test_a_future_birth_a_male_mother_and_a_stillbirth_before_the_cut_off_are_refused(): void
    {
        foreach ([[$this->mother(), ['born_at' => now()->addHour()->toDateTimeString()], 'born_at'], [$this->patient(['sex' => 'M']), [], 'mother_patient_id'], [$this->mother(), ['outcome' => 'stillbirth', 'gestation_weeks' => 22], 'gestation_weeks']] as [$mother, $change, $field]) {
            try {
                $this->service()->draft($mother, $this->birth($change), $this->actor());
                $this->fail("expected [$field] to be refused");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey($field, $e->errors());
            }
        }
        $this->assertSame(0, VrBirthNotification::count());
    }

    public function test_a_second_person_issues_it_and_the_writer_cannot(): void
    {
        $clerk = $this->actor();
        $n = $this->service()->draft($this->mother(), $this->birth(), $clerk);

        try {
            $this->service()->issue($n, $clerk, 'The father');
            $this->fail('the writer must not issue their own notification');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $checker = $this->actor();
        $issued = $this->service()->issue($n, $checker, 'Mr Karim, father');

        $this->assertSame(['issued', $checker->id, 'Mr Karim, father'], [$issued->status, $issued->verified_by, $issued->issued_to]);
        $this->assertNotNull($issued->issued_at);
        $this->expectException(ValidationException::class);
        $this->service()->issue($issued, $this->actor(), 'again');
    }

    public function test_the_registrars_number_is_recorded_only_after_issue(): void
    {
        $clerk = $this->actor();
        $n = $this->service()->draft($this->mother(), $this->birth(), $clerk);
        try {
            $this->service()->register($n, '2026123456789', $clerk);
            $this->fail('a draft cannot be registered');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('registrar_reference', $e->errors());
        }
        $this->service()->issue($n, $this->actor(), 'Father');

        $r = $this->service()->register($n->refresh(), '2026123456789', $clerk);

        $this->assertSame(['registered', '2026123456789'], [$r->status, $r->registrar_reference]);
    }

    public function test_a_notification_is_cancelled_with_a_reason_and_can_be_written_again(): void
    {
        $mother = $this->mother();
        $n = $this->service()->draft($mother, $this->birth(), $this->actor());
        try {
            $this->service()->cancel($n, 'no', $this->actor());
            $this->fail('a reason is needed');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cancel_reason', $e->errors());
        }

        $this->service()->cancel($n, 'The weight was wrong.', $this->actor());
        $again = $this->service()->draft($mother, $this->birth(), $this->actor());

        $this->assertSame('cancelled', $n->refresh()->status);
        $this->assertSame('draft', $again->status);
        $this->assertNotSame($n->number, $again->number);
    }

    public function test_the_family_home_is_checked_and_filled_in_from_the_lowest_place(): void
    {
        app(GeoInstaller::class)->install();
        $savar = GeoDistrict::query()->where('name_en', 'Dhaka')->firstOrFail()->upazilas()->where('name_en', 'Savar')->firstOrFail();

        $n = $this->service()->draft($this->mother(), $this->birth(['upazila_id' => $savar->id]), $this->actor());

        $this->assertSame([$savar->district_id, $savar->district->division_id], [$n->district_id, $n->division_id]);
    }

    public function test_births_issued_but_not_registered_after_the_follow_up_period_are_listed(): void
    {
        $clerk = $this->actor();
        $old = $this->service()->draft($this->mother(), $this->birth(), $clerk);
        $this->service()->issue($old, $this->actor(), 'Father');
        $old->update(['issued_at' => now()->subDays(50)]);
        $recent = $this->service()->draft($this->mother(), $this->birth(), $clerk);
        $this->service()->issue($recent, $this->actor(), 'Father');

        $list = $this->service()->overdueForRegistration($this->organization->id);

        $this->assertSame([$old->id], $list->pluck('id')->all());
    }

    public function test_a_father_s_national_id_is_stored_encrypted(): void
    {
        $n = $this->service()->draft($this->mother(), $this->birth(['father_nid' => '1990123456789']), $this->actor());

        $this->assertNotSame('1990123456789', DB::table('vr_birth_notifications')->where('id', $n->id)->value('father_nid'));
        $this->assertSame('1990123456789', $n->refresh()->father_nid);
    }
}
