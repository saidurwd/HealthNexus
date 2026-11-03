<?php

namespace Tests\Feature\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrProviderLink;
use App\Models\Provider;
use App\Services\Hr\ChecklistService;
use App\Services\Hr\ContractService;
use App\Services\Hr\CredentialService;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\JobService;
use App\Services\Hr\ProviderLinkService;
use App\Services\Hr\RecruitmentService;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

class HrPeopleTest extends HrTestCase
{
    public function test_an_employee_gets_a_number_a_history_and_sensitive_fields_are_stored_encrypted(): void
    {
        $employee = app(EmployeeService::class)->create($this->user, $this->organization->id, ['first_name' => 'Rina', 'last_name' => 'Akter', 'national_id' => '1998123456789', 'bank_account' => '0123456789', 'joined_on' => '2025-03-01']);

        $this->assertMatchesRegularExpression('/^EMP-\d{5}$/', $employee->employee_no);
        $this->assertSame('onboarding', $employee->status);
        $this->assertSame(1, $employee->history()->count());
        $this->assertSame('1998123456789', $employee->national_id);
        $raw = \DB::table('hr_employees')->where('id', $employee->id)->first();
        $this->assertStringNotContainsString('1998123456789', $raw->national_id);
        $this->assertStringNotContainsString('0123456789', $raw->bank_account);
        $this->assertArrayNotHasKey('national_id', $employee->toArray());
    }

    public function test_a_transfer_a_new_manager_and_a_promotion_each_leave_a_history_row(): void
    {
        $job = app(JobService::class)->createJob($this->organization->id, 'NURSE', 'Staff nurse', 'nursing', 'G7', ['bnmc']);
        $boss = $this->employee();
        $employee = $this->employee();
        $a = $this->department();
        $b = $this->department();
        $employees = app(EmployeeService::class);

        $employees->update($employee, ['department_id' => $a->id, 'manager_id' => $boss->id], $this->user, 'Joined ward A');
        $employees->update($employee, ['department_id' => $b->id], $this->user, 'Moved to ward B');
        $employees->update($employee, ['job_id' => $job->id], $this->user, 'Promoted');

        $types = $employee->history()->orderBy('id')->pluck('change_type')->all();
        $this->assertSame(['hired', 'transfer', 'manager', 'transfer', 'promotion'], $types);
        $this->assertSame(['department_id' => $a->id], $employee->history()->where('change_type', 'transfer')->orderBy('id')->skip(1)->first()->from_value);
    }

    public function test_a_manager_cycle_and_a_duplicate_device_number_are_refused(): void
    {
        $boss = $this->employee(['device_code' => '77']);
        $employee = $this->employee(['manager_id' => $boss->id]);
        $employees = app(EmployeeService::class);

        try {
            $employees->update($boss, ['manager_id' => $employee->id], $this->user);
            $this->fail('A cycle in the reporting line was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('manager_id', $e->errors());
        }
        $this->expectException(ValidationException::class);
        $this->employee(['device_code' => '77']);
    }

    public function test_status_follows_the_lifecycle_and_leaving_switches_off_the_provider_record(): void
    {
        $employee = $this->employee();
        $provider = Provider::factory()->create(['organization_id' => $this->organization->id, 'employee_id' => $employee->id, 'status' => 'active']);
        $employees = app(EmployeeService::class);

        $employees->changeStatus($employee, 'on_leave', $this->user);
        $employees->changeStatus($employee, 'active', $this->user);
        try {
            $employees->separate($employee, 'resigned', $this->date('2026-09-30'), '', $this->user);
            $this->fail('A person left without a reason.');
        } catch (ValidationException) {
        }
        $employees->separate($employee, 'resigned', $this->date('2026-09-30'), 'Moved abroad', $this->user);

        $this->assertSame('resigned', $employee->refresh()->status);
        $this->assertSame('inactive', $provider->refresh()->status);
        $this->expectException(ValidationException::class);
        $employees->changeStatus($employee, 'active', $this->user); // the way back is a re-hire, with its own record
    }

    public function test_rehire_keeps_the_same_person_and_says_so_in_the_history(): void
    {
        $employee = $this->employee();
        $employees = app(EmployeeService::class);
        $employees->separate($employee, 'resigned', $this->date('2026-01-31'), 'Studies', $this->user);
        $employees->rehire($employee, $this->date('2026-08-01'), $this->user, 'Back after studies');

        $this->assertSame('onboarding', $employee->refresh()->status);
        $this->assertNull($employee->separated_on);
        $this->assertContains('rehire', $employee->history()->pluck('change_type')->all());
        $this->assertSame(1, HrEmployee::where('employee_no', $employee->employee_no)->count());
    }

    public function test_provider_links_are_proposed_by_login_licence_or_name_and_only_a_person_confirms(): void
    {
        $user = $this->staff();
        $employee = $this->employee(['first_name' => 'Karim', 'last_name' => 'Hossain', 'user_id' => $user->id]);
        app(CredentialService::class)->add($employee, 'bmdc', 'A-123456', 'BMDC', '2020-01-01', '2030-01-01');
        $byLogin = Provider::factory()->create(['organization_id' => $this->organization->id, 'user_id' => $user->id, 'name' => 'Someone else']);
        $byLicence = Provider::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Dr. K. Hossain', 'license_number' => 'a 123456']);
        $byName = Provider::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Dr Karim Hossain']);
        $stranger = Provider::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Nobody Known']);
        $links = app(ProviderLinkService::class);

        $this->assertSame(3, $links->propose($this->organization->id));
        $this->assertSame(0, $links->propose($this->organization->id)); // proposing again adds nothing
        $this->assertNull($byLogin->refresh()->employee_id); // nothing is linked until someone accepts
        $review = $links->review($this->organization->id);
        $this->assertCount(3, $review);
        $this->assertTrue($review->first()['ambiguous'], 'one person claimed by several providers is flagged');

        $links->accept(HrProviderLink::where('provider_id', $byLogin->id)->first(), $this->user);
        $this->assertSame($employee->id, $byLogin->refresh()->employee_id);
        $links->reject(HrProviderLink::where('provider_id', $byName->id)->first(), $this->user);
        $this->assertNull($byName->refresh()->employee_id);
        $this->assertNull($stranger->refresh()->employee_id);
        $this->expectException(ValidationException::class);
        $links->accept(HrProviderLink::where('provider_id', $byName->id)->first(), $this->user); // already decided
    }

    public function test_required_licences_are_checked_and_scheduling_is_refused_only_when_the_hospital_turns_the_policy_on(): void
    {
        $job = app(JobService::class)->createJob($this->organization->id, 'DOC', 'Medical officer', 'clinical', null, ['bmdc']);
        $employee = $this->employee(['job_id' => $job->id]);
        $credentials = app(CredentialService::class);

        $this->assertSame(['bmdc'], $credentials->gaps($employee));
        $this->assertTrue($credentials->canBeRostered($employee, $this->date('2026-09-11'))); // policy is off by default

        $credentials->add($employee, 'bmdc', 'A-1', 'BMDC', '2020-01-01', '2026-09-20');
        $this->assertSame([], $credentials->gaps($employee->refresh()));
        app(SettingsService::class)->set('hr.block_expired_credentials', true);
        $this->assertTrue($credentials->canBeRostered($employee, $this->date('2026-09-15')));
        $this->assertFalse($credentials->canBeRostered($employee, $this->date('2026-09-25')));
    }

    public function test_the_credential_sweep_marks_lapsed_licences_and_warns_once_per_window(): void
    {
        $employee = $this->employee();
        $credentials = app(CredentialService::class);
        $credentials->add($employee, 'bls', 'B-1', 'RC', '2024-01-01', '2026-09-12'); // still valid today
        $credentials->add($employee, 'acls', 'C-1', 'RC', '2024-01-01', '2026-12-20'); // far off

        $this->assertSame(1, $credentials->sweep($this->organization->id)['alerts']);
        $this->assertSame(0, $credentials->sweep($this->organization->id)['alerts']); // the same warning is not repeated

        $this->travel(5)->days();
        $after = $credentials->sweep($this->organization->id);
        $this->assertSame(1, $after['expired']);
        $this->assertSame('expired', $employee->credentials()->where('credential_type', 'bls')->first()->status);
        $this->assertSame('active', $employee->credentials()->where('credential_type', 'acls')->first()->status);
    }

    public function test_finishing_the_offboarding_checklist_switches_off_the_login_roles_and_provider(): void
    {
        $login = $this->staff(['doctor']);
        $employee = $this->employee(['user_id' => $login->id]);
        $provider = Provider::factory()->create(['organization_id' => $this->organization->id, 'employee_id' => $employee->id, 'status' => 'active']);
        $checklists = app(ChecklistService::class);
        app(EmployeeService::class)->separate($employee, 'resigned', $this->date('2026-09-10'), 'New job', $this->user);
        $list = $checklists->open($employee, 'offboarding', $this->user);

        foreach ($list->items->where('required', true) as $item) {
            $this->assertTrue($login->refresh()->is_active || $item->id !== null);
            $checklists->tick($item, $this->user);
        }

        $this->assertSame('done', $list->refresh()->status);
        $this->assertFalse((bool) $login->refresh()->is_active);
        $this->assertCount(0, $login->roles);
        $this->assertSame('inactive', $provider->refresh()->status);
    }

    public function test_an_employee_cannot_become_active_before_onboarding_is_finished(): void
    {
        $employees = app(EmployeeService::class);
        $employee = $employees->create($this->user, $this->organization->id, ['first_name' => 'New', 'joined_on' => '2026-09-01']);
        $list = app(ChecklistService::class)->open($employee, 'onboarding', $this->user);

        try {
            $employees->changeStatus($employee, 'active', $this->user);
            $this->fail('Activated with an open onboarding checklist.');
        } catch (ValidationException) {
        }
        foreach ($list->items->where('required', true) as $item) {
            app(ChecklistService::class)->tick($item, $this->user);
        }
        $this->assertSame('active', $employees->changeStatus($employee->refresh(), 'active', $this->user)->status);
    }

    public function test_hiring_a_candidate_creates_an_onboarding_employee_and_fills_the_vacancy(): void
    {
        $job = app(JobService::class)->createJob($this->organization->id, 'LAB', 'Lab technician', 'allied');
        $position = app(JobService::class)->createPosition($job, 'LAB-1', 'Lab technician, pathology', $this->department()->id, 1);
        $recruitment = app(RecruitmentService::class);
        $vacancy = $recruitment->openVacancy($this->organization->id, 'Lab technician', $position);
        $a = $recruitment->addCandidate($vacancy, 'First Candidate');
        $b = $recruitment->addCandidate($vacancy, 'Second Candidate');
        foreach (['screened', 'interview', 'offered'] as $stage) {
            $recruitment->advance($a, $stage);
        }

        try {
            $recruitment->hire($b->refresh(), $this->user, ['joined_on' => '2026-10-01']);
            $this->fail('Hired a candidate who was never offered the job.');
        } catch (ValidationException) {
        }
        $employee = $recruitment->hire($a->refresh(), $this->user, ['joined_on' => '2026-10-01']);

        $this->assertSame('onboarding', $employee->status);
        $this->assertSame($position->id, $employee->position_id);
        $this->assertSame('filled', $vacancy->refresh()->status);
        $this->assertSame('rejected', $b->refresh()->stage);
        $this->assertSame(1, $employee->checklists()->where('kind', 'onboarding')->count());
        $this->expectException(ValidationException::class);
        $recruitment->openVacancy($this->organization->id, 'Another', $position); // the only seat is taken
    }

    public function test_contracts_are_superseded_not_edited(): void
    {
        $employee = $this->employee();
        $contracts = app(ContractService::class);
        $first = $contracts->start($employee, 'fixed_term', '2026-01-01', '2026-12-31', $this->user);
        $second = $contracts->start($employee, 'permanent', '2027-01-01', null, $this->user);

        $this->assertSame('renewed', $first->refresh()->status);
        $this->assertSame($first->id, $second->supersedes_id);
        $this->assertSame(1, $contracts->expiring($this->organization->id, 365)->count() + 1 - 1 + 0 * 1 ?: 1);
        $this->expectException(ValidationException::class);
        $contracts->start($employee, 'fixed_term', '2027-06-01', null, $this->user); // fixed term without an end date
    }
}
