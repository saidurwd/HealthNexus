<?php

namespace Tests\Feature\Hr;

use App\Models\Branch;
use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrPaymentBatch;
use App\Models\Hr\HrPayrollRun;
use App\Models\Hr\HrProviderLink;
use App\Models\Hr\HrPunch;
use App\Models\Hr\HrShift;
use App\Models\Organization;
use App\Models\Provider;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\ChecklistService;
use App\Services\Hr\CompensationService;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\JobService;
use App\Services\Hr\LeaveBalanceService;
use App\Services\Hr\PaymentFileService;
use App\Services\Hr\PayrollService;
use App\Services\Hr\RecruitmentService;
use App\Services\Hr\RosterService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HrScreensTest extends HrTestCase
{
    /** @return array<string, mixed> one of everything so each screen has something to draw */
    private function everything(): array
    {
        $department = $this->department();
        $login = $this->staff();
        $job = app(JobService::class)->createJob($this->organization->id, 'NURSE', 'Staff nurse', 'nursing', null, ['bnmc']);
        $position = app(JobService::class)->createPosition($job, 'N-1', 'Staff nurse, ward', $department->id, 5);
        $employee = $this->employee(['department_id' => $department->id, 'job_id' => $job->id, 'user_id' => $login->id, 'device_code' => '55', 'bank_account' => '1234567890', 'bank_name' => 'City Bank']);
        $this->salary($employee);
        app(LeaveBalanceService::class)->openYear($this->organization->id, 2026);
        $shift = $this->shift('DAY');
        app(RosterService::class)->assign($employee, $this->date('2026-09-14'), $shift, $this->user);
        app(RosterService::class)->require($this->organization->id, $department->id, $shift, 2);
        app(AttendanceService::class)->processRange($this->organization->id, $this->date('2026-08-01'), $this->date('2026-09-10'));
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $payroll->openPeriod($this->organization->id, $this->date('2026-08-10'))));
        $payroll->approve($run, $this->actor(['payroll.run.approve']));
        $payroll->publishPayslips($run->refresh());
        app(PaymentFileService::class)->prepare($run, 'bank', $this->user);
        $vacancy = app(RecruitmentService::class)->openVacancy($this->organization->id, 'Nurse', $position);
        app(RecruitmentService::class)->addCandidate($vacancy, 'Candidate One');
        $newbie = app(EmployeeService::class)->create($this->user, $this->organization->id, ['first_name' => 'New', 'joined_on' => '2026-09-01']);
        app(ChecklistService::class)->open($newbie, 'onboarding', $this->user);

        return compact('department', 'login', 'job', 'employee', 'run', 'vacancy', 'newbie');
    }

    public function test_every_screen_draws_for_a_super_admin(): void
    {
        $d = $this->everything();
        $batch = HrPaymentBatch::first();
        $line = $d['run']->lines()->first();
        foreach ([
            route('admin.hr.dashboard'), route('admin.hr.employees'), route('admin.hr.employees', ['status' => 'onboarding']), route('admin.hr.employees.show', $d['employee']), route('admin.hr.employees.show', $d['newbie']), route('admin.hr.jobs'),
            route('admin.hr.provider-links'), route('admin.hr.vacancies'), route('admin.hr.training'), route('admin.hr.reports'), route('admin.hr.reports', ['report' => 'turnover']), route('admin.hr.reports', ['report' => 'absenteeism']),
            route('admin.hr.reports', ['report' => 'credentials']), route('admin.hr.reports', ['report' => 'overtime']), route('admin.hr.reports', ['report' => 'cost']), route('admin.hr.shifts'), route('admin.hr.roster', ['from' => '2026-09-14']),
            route('admin.hr.attendance'), route('admin.hr.attendance', ['date' => '2026-08-14']), route('admin.hr.corrections'), route('admin.hr.overtime'), route('admin.hr.leave'), route('admin.hr.leave.balances'), route('admin.hr.leave.types'),
            route('admin.hr.payroll'), route('admin.hr.payroll.show', $d['run']), route('admin.hr.compensation'), route('admin.hr.payslips.show', $line),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.hr.payment-files.download', $batch))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_the_employee_screens_show_private_details_only_to_those_allowed_and_never_in_full(): void
    {
        $d = $this->everything();
        $d['employee']->update(['national_id' => '1998123456789']);
        $viewer = $this->actor(['hr.employee.view']);
        $this->actingAs($viewer);
        $this->get(route('admin.hr.employees.show', $d['employee']))->assertOk()->assertDontSee('1998123456789')->assertDontSee('1234567890')->assertDontSee('Private details');

        $payrollUser = $this->actor(['hr.employee.view', 'hr.employee.sensitive']);
        $this->actingAs($payrollUser);
        $page = $this->get(route('admin.hr.employees.show', $d['employee']))->assertOk();
        $page->assertSee('Private details')->assertSee('•••••••••6789', false)->assertSee('••••••7890', false)->assertDontSee('1998123456789')->assertDontSee('1234567890');
    }

    public function test_salary_figures_are_for_the_compensation_permission_only(): void
    {
        $d = $this->everything();
        $this->actingAs($this->actor(['hr.employee.view']));
        $this->get(route('admin.hr.employees.show', $d['employee']))->assertOk()->assertDontSee('Salary structure');
        $this->actingAs($this->actor(['hr.employee.view', 'compensation.structure.view']));
        $this->get(route('admin.hr.employees.show', $d['employee']))->assertOk()->assertSee('Salary structure');
        $this->actingAs($this->actor(['hr.employee.view']));
        $this->get(route('admin.hr.payroll'))->assertForbidden();
        $this->get(route('admin.hr.payroll.show', $d['run']))->assertForbidden();
        $this->get(route('admin.hr.compensation'))->assertForbidden();
    }

    public function test_an_employee_sees_only_their_own_published_payslip(): void
    {
        $other = $this->employee(['user_id' => $this->staff()->id]);
        $this->salary($other);
        $d = $this->everything(); // the August run includes both people
        $mine = $d['run']->lines()->where('employee_id', $d['employee']->id)->first();
        $theirs = $d['run']->lines()->where('employee_id', $other->id)->first();

        $this->actingAs($d['login']->refresh()->givePermissionTo(Permission::findOrCreate('hr.self.view', 'web')));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->get(route('admin.hr.me'))->assertOk()->assertSee('August 2026');
        $this->get(route('admin.hr.me.payslip', $mine->id))->assertOk();
        $this->get(route('admin.hr.me.payslip', $theirs->id))->assertNotFound();
        $this->get(route('admin.hr.payslips.show', $mine))->assertForbidden(); // the HR route needs the payroll permission
    }

    public function test_a_login_not_linked_to_an_employee_gets_a_clear_refusal_and_records_of_other_organizations_do_not_exist(): void
    {
        $d = $this->everything();
        $this->actingAs($this->actor(['hr.self.view']));
        $this->get(route('admin.hr.me'))->assertForbidden();

        $other = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $other->id]);
        $outsider = $this->staff(['super_admin'], $other, $otherBranch);
        $this->actingAs($outsider);
        $this->context($other, $otherBranch);
        $this->get(route('admin.hr.employees.show', $d['employee']))->assertNotFound();
        $this->get(route('admin.hr.payroll.show', $d['run']))->assertNotFound();
        $this->get(route('admin.hr.payslips.show', $d['run']->lines()->first()))->assertNotFound();
        $this->get(route('admin.hr.payment-files.download', HrPaymentBatch::first()))->assertNotFound();
        $this->post(route('admin.hr.roster.assign'), ['employee' => $d['employee']->public_id, 'work_date' => '2026-09-21', 'shift_id' => 1])->assertNotFound();
    }

    public function test_adding_an_employee_and_running_the_status_actions_through_the_screens(): void
    {
        $this->post(route('admin.hr.employees.store'), ['first_name' => 'Nasrin', 'last_name' => 'Sultana', 'gender' => 'female', 'joined_on' => '2026-09-01', 'category' => 'permanent'])->assertRedirect();
        $employee = HrEmployee::where('first_name', 'Nasrin')->firstOrFail();
        $this->assertSame('onboarding', $employee->status);
        $this->assertSame(1, $employee->checklists()->count());

        foreach ($employee->checklists()->first()->items()->where('required', true)->get() as $item) {
            $this->post(route('admin.hr.checklist.tick', $item))->assertRedirect();
        }
        $this->post(route('admin.hr.employees.status', $employee), ['action' => 'active'])->assertRedirect();
        $this->assertSame('active', $employee->refresh()->status);
        $this->post(route('admin.hr.employees.status', $employee), ['action' => 'resigned', 'reason' => 'Moved abroad', 'date' => '2026-09-30'])->assertRedirect();
        $this->assertSame('resigned', $employee->refresh()->status);
        $this->assertSame(1, $employee->checklists()->where('kind', 'offboarding')->count());
    }

    public function test_the_payroll_run_through_the_screens_needs_two_people(): void
    {
        $e = $this->employee();
        $this->salary($e);
        app(AttendanceService::class)->processRange($this->organization->id, $this->date('2026-08-01'), $this->date('2026-08-31'));
        $creator = $this->actor(['payroll.run.view', 'payroll.run.create']);
        $this->actingAs($creator);
        $this->post(route('admin.hr.payroll.store'), ['month' => '2026-08', 'run_type' => 'regular'])->assertRedirect();
        $run = HrPayrollRun::firstOrFail();
        $this->assertSame('calculated', $run->status);

        $this->post(route('admin.hr.payroll.move', $run), ['action' => 'approve'])->assertForbidden(); // may create, may not approve
        $approver = $this->actor(['payroll.run.view', 'payroll.run.approve']);
        $this->actingAs($approver);
        $this->post(route('admin.hr.payroll.move', $run), ['action' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $run->refresh()->status);
    }

    public function test_punches_can_be_added_from_the_attendance_screen(): void
    {
        $e = $this->employee(['device_code' => '31']);
        app(RosterService::class)->assign($e, $this->date('2026-09-08'), $this->shift('DAY'), $this->user);
        $this->post(route('admin.hr.attendance.punches'), ['punches' => "31, 2026-09-08 08:01:00\n31, 2026-09-08 16:02:00\n77, 2026-09-08 09:00:00\nnonsense"])->assertRedirect();
        $this->assertSame(3, HrPunch::count());
        $this->assertSame('present', HrAttendanceDay::where('employee_id', $e->id)->first()->status);
        $this->get(route('admin.hr.attendance', ['date' => '2026-09-08']))->assertOk()->assertSee('77');
    }

    public function test_the_provider_review_screen_links_only_on_a_decision(): void
    {
        $login = $this->staff();
        $employee = $this->employee(['first_name' => 'Karim', 'last_name' => 'Hossain', 'user_id' => $login->id]);
        $provider = Provider::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Dr Karim Hossain']);
        $this->post(route('admin.hr.provider-links.propose'))->assertRedirect();
        $this->get(route('admin.hr.provider-links'))->assertOk()->assertSee('Karim Hossain');
        $this->assertNull($provider->refresh()->employee_id);
        $link = HrProviderLink::firstOrFail();
        $this->post(route('admin.hr.provider-links.decide', $link), ['decision' => 'accept'])->assertRedirect();
        $this->assertSame($employee->id, $provider->refresh()->employee_id);
    }

    public function test_the_hr_pay_setup_and_roster_screens_save_through_services(): void
    {
        app(CompensationService::class)->seedComponents($this->organization->id);
        $this->post(route('admin.hr.compensation.slabs'), ['effective_from' => '2026-07-01', 'slabs' => "0,400000,0\n400000,,10"])->assertRedirect();
        $this->assertDatabaseCount('hr_tax_slabs', 2);
        $this->post(route('admin.hr.shifts.store'), ['code' => 'NIGHT', 'name' => 'Night', 'start_time' => '22:00', 'end_time' => '06:00'])->assertRedirect();
        $this->assertTrue((bool) HrShift::where('code', 'NIGHT')->value('is_night'));
    }
}
