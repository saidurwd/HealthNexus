<?php

namespace Tests\Feature\Dashboard;

use App\Models\Patient;
use App\Models\User;
use App\Services\Dashboard\HomeTiles;
use App\Services\ModuleManager;
use Spatie\Permission\Models\Role;

/** The home page: what is waiting for this person, only from the work their rights allow and the modules their hospital has on. */
class HomeTest extends FrontDoorTestCase
{
    private function tiles(User $user): array
    {
        return collect(app(HomeTiles::class)->forUser($user, $this->organization->id))->keyBy('key')->all();
    }

    public function test_a_person_sees_only_the_work_their_rights_allow(): void
    {
        $lab = $this->actor(['lab.dashboard.view']);
        $keys = array_keys($this->tiles($lab));
        sort($keys);
        $this->assertSame(['lab', 'notices'], $keys, 'the lab and the notices, nothing else');

        $this->assertSame(['notices'], array_keys($this->tiles($this->actor([]))));
        $many = array_keys($this->tiles($this->actor(['lab.dashboard.view', 'pharmacy.dashboard.view', 'nursing.dashboard.view', 'appointments.queue'])));
        foreach (['lab', 'pharmacy', 'nursing', 'queue'] as $k) {
            $this->assertContains($k, $many);
        }
    }

    public function test_a_module_the_hospital_has_switched_off_gives_no_tile(): void
    {
        $user = $this->actor(['lab.dashboard.view', 'pharmacy.dashboard.view']);
        app(ModuleManager::class)->set('lab', false, $this->organization->id);

        $this->assertArrayNotHasKey('lab', $this->tiles($user));
        $this->assertArrayHasKey('pharmacy', $this->tiles($user));
    }

    public function test_the_counts_are_the_work_that_is_really_waiting(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id])->id;
        foreach (['registered', 'awaiting_collection', 'received', 'reported', 'cancelled'] as $i => $status) {
            $this->row('lab_orders', ['status' => $status, 'order_number' => "L-$i", 'patient_id' => $patient, 'encounter_id' => 1]);
        }
        foreach (['pending', 'partially_dispensed', 'fully_dispensed'] as $i => $status) {
            $this->row('pharmacy_orders', ['status' => $status, 'order_number' => "P-$i", 'patient_id' => $patient, 'prescription_id' => 1]);
        }
        $this->row('nursing_tasks', ['status' => 'pending', 'due_at' => now()->subHour(), 'task_type' => 'obs', 'episode_id' => 1, 'admission_id' => 1, 'patient_id' => $patient]);
        $this->row('nursing_tasks', ['status' => 'pending', 'due_at' => now()->addHour(), 'task_type' => 'obs', 'episode_id' => 1, 'admission_id' => 1, 'patient_id' => $patient]);
        $this->row('nursing_tasks', ['status' => 'completed', 'due_at' => now()->subHour(), 'task_type' => 'obs', 'episode_id' => 1, 'admission_id' => 1, 'patient_id' => $patient]);

        $t = $this->tiles($this->actor(['lab.dashboard.view', 'pharmacy.dashboard.view', 'nursing.dashboard.view']));

        $this->assertSame(3, $t['lab']['count'], 'registered, awaiting collection and received; not reported or cancelled');
        $this->assertSame(2, $t['pharmacy']['count']);
        $this->assertSame(1, $t['nursing']['count'], 'only a pending task past its time');
    }

    public function test_counts_that_belong_to_the_person_are_theirs_and_a_person_never_approves_their_own(): void
    {
        $me = $this->actor(['billing.adjustment.approve', 'welfare.assessment.decide', 'welfare.grant.approve', 'quality.action.view']);
        $other = $this->actor([]);
        $this->row('billing_adjustments', ['invoice_id' => 1, 'type' => 'discount', 'status' => 'requested', 'requested_by' => $other->id, 'original_value' => 0, 'new_value' => 10, 'difference' => 10, 'reason' => 'x', 'currency' => 'BDT', 'adjustable_type' => 'x', 'adjustable_id' => 1, 'requested_at' => now()]);
        $this->row('billing_adjustments', ['invoice_id' => 1, 'type' => 'discount', 'status' => 'requested', 'requested_by' => $me->id, 'original_value' => 0, 'new_value' => 10, 'difference' => 10, 'reason' => 'x', 'currency' => 'BDT', 'adjustable_type' => 'x', 'adjustable_id' => 1, 'requested_at' => now()]);
        $this->row('wf_assessments', ['number' => 'WA-1', 'patient_id' => 1, 'status' => 'submitted', 'assessed_by' => $other->id]);
        $this->row('wf_assessments', ['number' => 'WA-2', 'patient_id' => 1, 'status' => 'submitted', 'assessed_by' => $me->id]);
        $this->row('wf_grants', ['number' => 'WG-1', 'patient_id' => 1, 'assessment_id' => 1, 'fund_id' => 1, 'cap_amount' => 1, 'percent' => 100, 'valid_from' => today(), 'valid_to' => today(), 'status' => 'requested', 'requested_by' => $me->id]);
        $this->row('qa_actions', ['number' => 'A-1', 'source_type' => 'x', 'source_id' => 1, 'action_type' => 'corrective', 'description' => 'd', 'owner_id' => $me->id, 'due_on' => today()->subDay(), 'status' => 'open']);
        $this->row('qa_actions', ['number' => 'A-2', 'source_type' => 'x', 'source_id' => 1, 'action_type' => 'corrective', 'description' => 'd', 'owner_id' => $other->id, 'due_on' => today()->subDay(), 'status' => 'open']);
        $this->row('qa_actions', ['number' => 'A-3', 'source_type' => 'x', 'source_id' => 1, 'action_type' => 'corrective', 'description' => 'd', 'owner_id' => $me->id, 'due_on' => today()->addDay(), 'status' => 'open']);

        $t = $this->tiles($me);

        $this->assertSame(1, $t['adjustments']['count'], 'the discount I asked for myself is not mine to approve');
        $this->assertSame(1, $t['welfare_assess']['count']);
        $this->assertSame(0, $t['welfare_grants']['count']);
        $this->assertSame(1, $t['quality']['count'], 'my own action that is past its date');
    }

    public function test_unread_notices_are_the_persons_own(): void
    {
        $a = $this->actor([]);
        $b = $this->actor([]);
        foreach ([[$a, false], [$a, false], [$a, true], [$b, false]] as [$u, $read]) {
            $this->row('notifications', ['user_id' => $u->id, 'title' => 'n', 'is_read' => $read, 'type' => 't', 'channel' => 'web', 'message' => 'm']);
        }

        $this->assertSame(2, $this->tiles($a)['notices']['count']);
        $this->assertSame(1, $this->tiles($b)['notices']['count']);
    }

    public function test_nothing_from_another_hospital_is_counted(): void
    {
        $other = $this->otherHospital();
        $this->row('lab_orders', ['organization_id' => $other->id, 'status' => 'registered', 'order_number' => 'X', 'patient_id' => 1, 'encounter_id' => 1]);

        $this->assertSame(0, $this->tiles($this->actor(['lab.dashboard.view']))['lab']['count']);
    }

    public function test_the_page_lists_what_waits_first_what_is_clear_after_and_links_each_to_its_list(): void
    {
        $this->row('lab_orders', ['status' => 'registered', 'order_number' => 'L-1', 'patient_id' => 1, 'encounter_id' => 1]);
        $user = $this->actor(['lab.dashboard.view', 'pharmacy.dashboard.view']);
        $html = $this->assertDashboardStatesItself('/dashboard', $user);

        $this->assertStringContainsString('What needs me now?', $html);
        $this->assertStringContainsString('Lab orders to work', $html);
        $this->assertStringContainsString(route('admin.lab.worklist.index'), $html, 'the tile opens the list it counts');
        $this->assertStringContainsString('All clear', $html);
        $this->assertStringContainsString('Prescriptions to dispense', $html, 'a clear item is named, not hidden');
        $this->assertStringContainsString('Hello, '.$user->name, $html);
    }

    public function test_the_widgets_the_page_always_had_are_still_there(): void
    {
        $user = $this->actor(['manage organizations', 'manage users', 'manage branches']);
        $r = $this->as($user)->get('/dashboard')->assertOk()->assertViewHas('widgets');
        $this->assertContains('total_users', collect($r->viewData('widgets'))->pluck('key'));
        $r->assertViewHas('tiles');
    }

    public function test_a_person_whose_role_has_a_worklist_gets_a_button_to_it(): void
    {
        Role::findOrCreate('cashier', 'web');
        $user = $this->actor(['billing.dashboard.view']);
        $user->assignRole('cashier');

        $this->as($user)->get('/dashboard')->assertOk()->assertSee('Open my worklist')->assertSee(route('admin.billing.cashier.index'), false);
    }

    public function test_the_counts_are_kept_a_minute_per_person_and_refresh_works_them_out_again(): void
    {
        $user = $this->actor(['lab.dashboard.view']);
        $this->row('lab_orders', ['status' => 'registered', 'order_number' => 'L-1', 'patient_id' => 1, 'encounter_id' => 1]);
        $this->assertSame(1, $this->tiles($user)['lab']['count']);
        $this->as($user)->get('/dashboard')->assertOk();
        $this->row('lab_orders', ['status' => 'registered', 'order_number' => 'L-2', 'patient_id' => 1, 'encounter_id' => 1]);

        $this->as($user)->get('/dashboard')->assertOk()->assertViewHas('tiles', fn ($t) => collect($t)->firstWhere('key', 'lab')['count'] === 1);
        $this->as($user)->get('/dashboard?fresh=1')->assertOk()->assertViewHas('tiles', fn ($t) => collect($t)->firstWhere('key', 'lab')['count'] === 2);
    }
}
