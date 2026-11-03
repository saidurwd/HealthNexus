<?php

namespace Tests\Feature\Mch;

use App\Models\Patient;
use App\Services\Mch\ImmunisationService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Vital\VitalTestCase;

class ImmunisationTest extends VitalTestCase
{
    private function svc(): ImmunisationService
    {
        return app(ImmunisationService::class);
    }

    private function child(int $daysOld): Patient
    {
        return $this->patient(['date_of_birth' => now()->subDays($daysOld)->toDateString(), 'sex' => 'F']);
    }

    private function states(Patient $c): array
    {
        return collect($this->svc()->card($c))->pluck('state', 'vaccine')->all();
    }

    public function test_a_newborns_card_shows_the_birth_doses_due_and_the_rest_not_yet_due(): void
    {
        $s = $this->states($this->child(1));

        $this->assertSame(['due', 'due', 'upcoming', 'upcoming'], [$s['bcg'], $s['opv0'], $s['penta1'], $s['mr2']]);
    }

    public function test_a_dose_is_overdue_only_after_the_grace_and_a_birth_dose_gets_its_own_period(): void
    {
        $s = $this->states($this->child(42 + 28 + 1));
        $this->assertSame(['overdue', 'overdue'], [$s['bcg'], $s['penta1']], 'both well past their age');

        $s = $this->states($this->child(42 + 10));
        $this->assertSame(['overdue', 'due', 'upcoming'], [$s['bcg'], $s['penta1'], $s['penta2']], 'the birth dose is overdue after 14 days; penta1 is due, not yet overdue');
    }

    public function test_giving_a_dose_needs_a_batch_and_records_it_once(): void
    {
        $c = $this->child(3);
        try {
            $this->svc()->give($c, 'bcg', [], $this->actor());
            $this->fail('a batch number is needed');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('batch_no', $e->errors());
        }

        $i = $this->svc()->give($c, 'bcg', ['batch_no' => 'B-2231', 'site' => 'left arm'], $this->actor());

        $this->assertSame(['given', 'B-2231'], [$i->status, $i->batch_no]);
        $this->assertSame('given', $this->states($c)['bcg']);
        $this->expectException(ValidationException::class);
        $this->svc()->give($c, 'bcg', ['batch_no' => 'B-2232'], $this->actor());
    }

    public function test_a_dose_given_elsewhere_is_recorded_from_the_card_without_a_batch(): void
    {
        $c = $this->child(60);

        $i = $this->svc()->give($c, 'bcg', ['status' => 'recorded', 'given_at' => now()->subDays(55)->toDateTimeString(), 'note' => 'Upazila health complex'], $this->actor());

        $this->assertSame(['recorded', null], [$i->status, $i->batch_no]);
    }

    public function test_an_unknown_dose_a_future_date_and_a_date_before_birth_are_refused(): void
    {
        $c = $this->child(30);
        foreach ([['mumps', []], ['bcg', ['given_at' => now()->addDay()->toDateTimeString(), 'batch_no' => 'x']], ['bcg', ['given_at' => now()->subDays(60)->toDateTimeString(), 'batch_no' => 'x']]] as [$v, $d]) {
            try {
                $this->svc()->give($c, $v, $d, $this->actor());
                $this->fail('expected a refusal');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_a_child_with_no_birth_date_cannot_have_a_card(): void
    {
        $c = $this->patient(['date_of_birth' => null]);
        $this->expectException(ValidationException::class);

        $this->svc()->card($c);
    }

    public function test_the_due_list_holds_young_children_with_something_due_most_overdue_first_and_only_this_hospital(): void
    {
        $fine = $this->child(1);
        $this->svc()->give($fine, 'bcg', ['batch_no' => 'B1'], $this->actor());
        $this->svc()->give($fine, 'opv0', ['batch_no' => 'B1'], $this->actor());
        $late = $this->child(120);
        $adult = $this->patient(['date_of_birth' => now()->subYears(30)->toDateString()]);
        $stranger = Patient::factory()->create(['date_of_birth' => now()->subDays(100)->toDateString()]);

        $ids = $this->svc()->dueList($this->organization->id, CarbonImmutable::now())->pluck('patient_id')->all();

        $this->assertSame([$late->id], $ids, 'the newborn has nothing due, the adult is too old, the stranger is another hospital');
        $this->assertNotContains($adult->id, $ids);
        $this->assertNotContains($stranger->id, $ids);
    }
}
