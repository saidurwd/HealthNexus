<?php

namespace Tests\Feature\Vital;

use App\Models\Vital\VrMedicolegalCase;
use App\Services\Vital\MedicoLegalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicoLegalTest extends VitalTestCase
{
    private function service(): MedicoLegalService
    {
        return app(MedicoLegalService::class);
    }

    private function case(array $o = []): VrMedicolegalCase
    {
        return $this->service()->register($this->patient(), $o + ['kind' => 'accident', 'brought_at' => now()->subHours(2)->toDateTimeString(), 'description' => 'Road accident, brought by bystanders.'], $this->actor());
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

    public function test_a_case_has_a_number_a_kind_and_needs_its_description(): void
    {
        $c = $this->case();

        $this->assertMatchesRegularExpression('/^ML-\d{4}-00001$/', $c->number);
        $this->assertSame('open', $c->status);
        $this->refused(fn () => $this->case(['kind' => 'dream']), 'kind');
        $this->refused(fn () => $this->case(['description' => 'short']), 'description');
        $this->refused(fn () => $this->case(['brought_at' => now()->addHour()->toDateTimeString()]), 'brought_at');
    }

    public function test_the_police_must_be_told_with_a_station_and_a_diary_number_before_anything_else(): void
    {
        $c = $this->case();
        $this->refused(fn () => $this->service()->requestPostmortem($c, $this->actor()), 'status');
        $this->refused(fn () => $this->service()->close($c, 'Done with it.', $this->actor()), 'status');
        $this->refused(fn () => $this->service()->informPolice($c, ['police_station' => 'Savar'], $this->actor()), 'gd_number');

        $told = $this->service()->informPolice($c, ['police_station' => ' Savar Model Thana ', 'gd_number' => 'GD-231', 'officer_name' => 'SI Rahman'], $this->actor());

        $this->assertSame(['police_informed', 'Savar Model Thana', 'GD-231'], [$told->status, $told->police_station, $told->gd_number]);
        $this->refused(fn () => $this->service()->informPolice($told, ['police_station' => 'x', 'gd_number' => 'y'], $this->actor()), 'status');
    }

    public function test_a_case_with_a_post_mortem_asked_for_closes_only_after_it_is_recorded(): void
    {
        $c = $this->case();
        $by = $this->actor();
        $this->service()->informPolice($c, ['police_station' => 'Savar', 'gd_number' => 'GD-1'], $by);
        $this->service()->requestPostmortem($c->refresh(), $by);

        $this->refused(fn () => $this->service()->close($c->refresh(), 'Nothing more to do.', $by), 'status');
        $this->service()->recordPostmortem($c->refresh(), $by);
        $this->refused(fn () => $this->service()->close($c->refresh(), 'no', $by), 'closure_note');
        $closed = $this->service()->close($c->refresh(), 'The post-mortem report was handed over.', $by);

        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->closed_at);
    }

    public function test_a_body_in_the_mortuary_is_held_until_the_case_is_closed(): void
    {
        $patient = $this->patient();
        $mortuary = $this->mortuaryCase($patient);
        $by = $this->actor();

        $c = $this->service()->register($patient, ['kind' => 'suspicious_death', 'brought_at' => now()->subHours(3)->toDateTimeString(), 'description' => 'Brought dead; cause unknown.'], $by);
        $row = fn () => DB::table('fac_mortuary_cases')->where('id', $mortuary)->first();
        $this->assertSame([1, 0, $mortuary], [(int) $row()->police_case, (int) $row()->police_cleared, $c->mortuary_case_id]);

        $this->service()->informPolice($c, ['police_station' => 'Savar', 'gd_number' => 'GD-9'], $by);
        $this->assertSame(0, (int) $row()->police_cleared, 'telling the police does not release the body');
        $this->service()->close($c->refresh(), 'The police order was received.', $by);
        $this->assertSame(1, (int) $row()->police_cleared);
    }

    public function test_cases_the_police_have_not_been_told_about_in_time_are_listed(): void
    {
        $late = $this->case(['brought_at' => now()->subHours(30)->toDateTimeString()]);
        $this->case(['brought_at' => now()->subHours(2)->toDateTimeString()]);
        $told = $this->case(['brought_at' => now()->subHours(40)->toDateTimeString()]);
        $this->service()->informPolice($told, ['police_station' => 'Savar', 'gd_number' => 'GD-3'], $this->actor());

        $this->assertSame([$late->id], $this->service()->notInformedInTime($this->organization->id)->pluck('id')->all());
    }
}
