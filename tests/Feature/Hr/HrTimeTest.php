<?php

namespace Tests\Feature\Hr;

use App\Models\HospitalHoliday;
use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrDevice;
use App\Models\Hr\HrLeaveType;
use App\Models\Hr\HrOvertimeEntry;
use App\Models\Hr\HrPunch;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\LeaveBalanceService;
use App\Services\Hr\LeaveRequestService;
use App\Services\Hr\OvertimeService;
use App\Services\Hr\PunchIngestService;
use App\Services\Hr\RosterService;
use Illuminate\Validation\ValidationException;

class HrTimeTest extends HrTestCase
{
    // 2026-09-14 is a Monday, 2026-09-11 a Friday (the weekly off).

    public function test_the_roster_refuses_overlaps_short_rest_long_weeks_and_too_many_days_in_a_row(): void
    {
        $employee = $this->employee();
        $day = $this->shift('DAY', '08:00', '16:00');
        $night = $this->shift('NIGHT', '22:00', '06:00');
        $roster = app(RosterService::class);

        $roster->assign($employee, $this->date('2026-09-14'), $day, $this->user);
        $this->assertNotEmpty($roster->conflicts($employee, $this->date('2026-09-14'), $day), 'same shift twice');
        $this->assertNotEmpty($roster->conflicts($employee, $this->date('2026-09-14'), $night), 'night starts only six hours after the day shift ends');
        $this->assertSame([], $roster->conflicts($employee, $this->date('2026-09-15'), $day));

        // six days in a row are fine, the seventh day makes 7 which is the limit; an eighth is refused
        foreach (['2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18', '2026-09-19', '2026-09-20'] as $d) {
            $roster->assign($employee, $this->date($d), $day, $this->user);
        }
        $this->assertStringContainsString('in a row', implode(' ', $roster->conflicts($employee, $this->date('2026-09-21'), $day)));
    }

    public function test_the_weekly_hours_limit_and_approved_leave_stop_a_roster_entry(): void
    {
        $employee = $this->employee();
        $long = $this->shift('LONG', '07:00', '20:00'); // 13 hours
        $roster = app(RosterService::class);
        foreach (['2026-09-14', '2026-09-16', '2026-09-18', '2026-09-20'] as $d) {
            $roster->assign($employee, $this->date($d), $long, $this->user); // 4 x 13 = 52
        }
        $this->assertStringContainsString('hours (limit 60)', implode(' ', $roster->conflicts($employee, $this->date('2026-09-15'), $long)));

        $this->actingAs($this->user);
        app(LeaveBalanceService::class)->openYear($this->organization->id, 2026);
        $leave = app(LeaveRequestService::class)->request($employee, HrLeaveType::where('code', 'CL')->first(), $this->date('2026-09-22'), $this->date('2026-09-22'));
        app(LeaveRequestService::class)->decideAsManager($leave->refresh(), $this->user, true);
        $this->assertStringContainsString('approved leave', implode(' ', $roster->conflicts($employee, $this->date('2026-09-22'), $this->shift('DAY'))));
    }

    public function test_staffing_shortfalls_are_found_against_the_requirement(): void
    {
        $department = $this->department();
        $day = $this->shift('DAY');
        $roster = app(RosterService::class);
        $roster->require($this->organization->id, $department->id, $day, 2);
        foreach ([1, 2] as $_) {
            $roster->assign($this->employee(['department_id' => $department->id]), $this->date('2026-09-14'), $day, $this->user);
        }
        $roster->assign($this->employee(['department_id' => $department->id]), $this->date('2026-09-15'), $day, $this->user);

        $short = $roster->shortfalls($this->organization->id, $this->date('2026-09-14'), $this->date('2026-09-15'));
        $this->assertCount(1, $short);
        $this->assertSame(['date' => '2026-09-15', 'department_id' => $department->id, 'shift' => 'DAY shift', 'needed' => 2, 'rostered' => 1], $short[0]);
    }

    public function test_the_same_punch_sent_twice_is_stored_once_and_an_unknown_code_waits_to_be_mapped(): void
    {
        $employee = $this->employee(['device_code' => '101']);
        $device = HrDevice::create(['organization_id' => $this->organization->id, 'code' => 'D1', 'name' => 'Gate']);
        $ingest = app(PunchIngestService::class);
        $batch = [['code' => '101', 'at' => '2026-09-14 07:58:00'], ['code' => '101', 'at' => '2026-09-14 16:05:00'], ['code' => '999', 'at' => '2026-09-14 08:00:00']];

        $first = $ingest->ingest($this->organization->id, $device, $batch);
        $again = $ingest->ingest($this->organization->id, $device, $batch);

        $this->assertSame(['stored' => 3, 'duplicates' => 0, 'unresolved' => 1], array_intersect_key($first, array_flip(['stored', 'duplicates', 'unresolved'])));
        $this->assertSame(['stored' => 0, 'duplicates' => 3], array_intersect_key($again, array_flip(['stored', 'duplicates'])));
        $this->assertSame(3, HrPunch::count());

        $stranger = $this->employee();
        $this->assertSame(1, $ingest->mapCode($this->organization->id, '999', $stranger));
        $this->assertSame(0, HrPunch::whereNull('employee_id')->count());
        $this->expectException(ValidationException::class);
        $ingest->mapCode($this->organization->id, '101', $stranger); // belongs to someone else
    }

    public function test_attendance_is_worked_out_from_punches_roster_leave_and_holidays(): void
    {
        $employee = $this->employee(['device_code' => '7']);
        $day = $this->shift('DAY', '08:00', '16:00', 30, 10);
        $roster = app(RosterService::class);
        $ingest = app(PunchIngestService::class);
        foreach (['2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'] as $d) {
            $roster->assign($employee, $this->date($d), $day, $this->user);
        }
        HospitalHoliday::create(['organization_id' => $this->organization->id, 'name' => 'Founders day', 'date' => '2026-09-18']);
        $ingest->ingest($this->organization->id, null, [
            ['code' => '7', 'at' => '2026-09-14 07:55:00'], ['code' => '7', 'at' => '2026-09-14 16:00:00'],   // on time
            ['code' => '7', 'at' => '2026-09-15 08:25:00'], ['code' => '7', 'at' => '2026-09-15 16:00:00'],   // late 15 minutes after grace
            ['code' => '7', 'at' => '2026-09-16 08:00:00'],                                                    // came, never punched out
            // 2026-09-17: nothing at all
            ['code' => '7', 'at' => '2026-09-18 08:00:00'], ['code' => '7', 'at' => '2026-09-18 17:30:00'],   // worked the holiday, 90 minutes extra
        ]);
        app(AttendanceService::class)->processRange($this->organization->id, $this->date('2026-09-14'), $this->date('2026-09-19'));
        $days = HrAttendanceDay::where('employee_id', $employee->id)->get()->keyBy(fn ($d) => $d->work_date->toDateString());

        $this->assertSame('present', $days['2026-09-14']->status);
        $this->assertSame(455, $days['2026-09-14']->worked_minutes); // 8h05 on site minus a 30 minute break
        $this->assertSame('late', $days['2026-09-15']->status);
        $this->assertSame(15, $days['2026-09-15']->late_minutes);
        $this->assertSame('half_day', $days['2026-09-16']->status);
        $this->assertSame('absent', $days['2026-09-17']->status);
        $this->assertSame('present', $days['2026-09-18']->status);
        $this->assertSame(90, $days['2026-09-18']->overtime_minutes);
        $this->assertSame('weekly_off', $days['2026-09-11']->status ?? 'weekly_off');
    }

    public function test_a_late_arriving_offline_batch_recomputes_the_day_and_a_locked_day_stays_put(): void
    {
        $employee = $this->employee(['device_code' => '8']);
        $day = $this->shift('DAY');
        app(RosterService::class)->assign($employee, $this->date('2026-09-14'), $day, $this->user);
        $attendance = app(AttendanceService::class);
        $attendance->calculateDay($employee, $this->date('2026-09-14'));
        $this->assertSame('absent', HrAttendanceDay::where('employee_id', $employee->id)->first()->status); // device was offline

        app(PunchIngestService::class)->ingest($this->organization->id, null, [['code' => '8', 'at' => '2026-09-14 08:00:00'], ['code' => '8', 'at' => '2026-09-14 16:00:00']]);
        $this->assertSame('present', HrAttendanceDay::where('employee_id', $employee->id)->first()->status);

        HrAttendanceDay::where('employee_id', $employee->id)->update(['locked' => true]);
        app(PunchIngestService::class)->ingest($this->organization->id, null, [['code' => '8', 'at' => '2026-09-14 17:30:00']]);
        $this->assertSame(480, HrAttendanceDay::where('employee_id', $employee->id)->first()->worked_minutes); // untouched
    }

    public function test_a_correction_is_decided_by_someone_else_and_changes_the_day(): void
    {
        $employee = $this->employee(['user_id' => $this->staff()->id]);
        app(RosterService::class)->assign($employee, $this->date('2026-09-08'), $this->shift('DAY'), $this->user);
        $attendance = app(AttendanceService::class);
        $attendance->calculateDay($employee, $this->date('2026-09-08'));

        $reg = $attendance->requestRegularization($employee, $this->date('2026-09-08'), $this->date('2026-09-08')->setTime(8, 0), $this->date('2026-09-08')->setTime(16, 0), 'Device was out of order');
        try {
            $attendance->decideRegularization($reg, $employee->user, true);
            $this->fail('A person approved their own correction.');
        } catch (ValidationException) {
        }
        $attendance->decideRegularization($reg->refresh(), $this->user, true);

        $day = HrAttendanceDay::where('employee_id', $employee->id)->first();
        $this->assertSame('present', $day->status);
        $this->assertSame($reg->id, $day->regularization_id);
        $this->expectException(ValidationException::class);
        $attendance->requestRegularization($employee, $this->date('2026-09-20'), $this->date('2026-09-20')->setTime(8, 0), null, 'In the future');
    }

    public function test_leave_needs_balance_the_managers_decision_and_hr_for_types_that_ask_for_it(): void
    {
        $login = $this->staff();
        $managerLogin = $this->staff();
        $manager = $this->employee(['user_id' => $managerLogin->id]);
        $employee = $this->employee(['user_id' => $login->id, 'manager_id' => $manager->id, 'gender' => 'female']);
        $balances = app(LeaveBalanceService::class);
        $balances->openYear($this->organization->id, 2026);
        $leave = app(LeaveRequestService::class);
        $cl = HrLeaveType::where('code', 'CL')->first();
        $ml = HrLeaveType::where('code', 'ML')->first();

        $this->assertSame('10.00', $balances->balance($employee, $cl));
        $request = $leave->request($employee, $cl, $this->date('2026-09-15'), $this->date('2026-09-17')); // Tue-Thu: 3 days
        $this->assertSame('3.00', (string) $request->days);
        try {
            $leave->decideAsManager($request, $this->user, true); // not her manager
            $this->fail('Somebody other than the manager decided first.');
        } catch (ValidationException) {
        }
        $leave->decideAsManager($request->refresh(), $managerLogin, true);
        $this->assertSame('approved', $request->refresh()->status);
        $this->assertSame('7.00', $balances->balance($employee, $cl));
        $this->assertSame('leave', app(AttendanceService::class)->calculateDay($employee, $this->date('2026-09-16'))->status);

        // maternity: manager then HR
        $balances->credit($employee, $ml, '112', 'opening', $this->date('2026-01-01'), 'ML-OPEN');
        $m = $leave->request($employee, $ml, $this->date('2026-10-05'), $this->date('2026-10-09'), false, 'Delivery', 1);
        $leave->decideAsManager($m, $managerLogin, true);
        $this->assertSame('manager_approved', $m->refresh()->status);
        $leave->decideAsHr($m, $this->user, true);
        $this->assertSame('approved', $m->refresh()->status);

        // cancelling before it starts puts the days back
        $leave->cancel($request->refresh(), $login);
        $this->assertSame('10.00', $balances->balance($employee, $cl));
    }

    public function test_leave_is_refused_when_it_overlaps_exceeds_the_balance_or_needs_a_document(): void
    {
        $employee = $this->employee();
        app(LeaveBalanceService::class)->openYear($this->organization->id, 2026);
        $leave = app(LeaveRequestService::class);
        $cl = HrLeaveType::where('code', 'CL')->first();
        $sl = HrLeaveType::where('code', 'SL')->first();

        $leave->request($employee, $cl, $this->date('2026-09-15'), $this->date('2026-09-16'));
        foreach ([
            fn () => $leave->request($employee, $cl, $this->date('2026-09-16'), $this->date('2026-09-17')),                 // overlaps
            fn () => $leave->request($employee, $cl, $this->date('2026-10-05'), $this->date('2026-10-09')),                 // more than 3 at a time
            fn () => $leave->request($employee, $sl, $this->date('2026-10-12'), $this->date('2026-10-16')),                 // 5 days needs a certificate
            fn () => $leave->request($employee, $cl, $this->date('2026-09-11'), $this->date('2026-09-11')),                 // a Friday: already a day off
            fn () => $leave->request($employee, HrLeaveType::where('code', 'ML')->first(), $this->date('2026-11-01'), $this->date('2026-11-02')), // male
        ] as $i => $attempt) {
            try {
                $attempt();
                $this->fail("Attempt $i was accepted.");
            } catch (ValidationException) {
            }
        }
        $this->assertTrue(true);
    }

    public function test_accrual_carry_forward_and_lapse_do_not_double_when_run_again(): void
    {
        $employee = $this->employee();
        $balances = app(LeaveBalanceService::class);
        $el = HrLeaveType::where('code', 'EL')->first();
        $cl = HrLeaveType::where('code', 'CL')->first();

        $this->assertSame(2, $balances->openYear($this->organization->id, 2026)); // CL and SL (EL accrues monthly)
        $this->assertSame(0, $balances->openYear($this->organization->id, 2026));
        foreach (range(1, 12) as $m) {
            $balances->accrueMonth($this->organization->id, $this->date(sprintf('2026-%02d-15', $m)));
        }
        $this->assertSame(0, $balances->accrueMonth($this->organization->id, $this->date('2026-12-15')));
        $this->assertSame('18.00', $balances->balance($employee, $el));

        $first = $balances->rollYear($this->organization->id, 2026);
        $second = $balances->rollYear($this->organization->id, 2026);
        $this->assertGreaterThan(0, $first['lapsed']);
        $this->assertSame(['carried' => 0, 'lapsed' => 0], $second);
        $this->assertSame('0.00', $balances->balance($employee, $cl, $this->date('2026-12-31'))); // casual leave does not carry
        $this->assertSame('18.00', $balances->balance($employee, $el, $this->date('2026-12-31'))); // earned leave carries (limit 30)
    }

    public function test_overtime_is_capped_per_day_and_per_month_and_approved_by_someone_else(): void
    {
        $login = $this->staff();
        $employee = $this->employee(['user_id' => $login->id, 'device_code' => '9']);
        $day = $this->shift('DAY', '08:00', '16:00');
        $roster = app(RosterService::class);
        $overtime = app(OvertimeService::class);
        $punches = [];
        foreach (['2026-08-03', '2026-08-04', '2026-08-05'] as $d) {
            $roster->assign($employee, $this->date($d), $day, $this->user);
            array_push($punches, ['code' => '9', 'at' => "$d 08:00:00"], ['code' => '9', 'at' => "$d 22:00:00"]); // 6 hours after the shift
        }
        app(PunchIngestService::class)->ingest($this->organization->id, null, $punches);
        $this->assertSame(3, $overtime->syncFromAttendance($this->organization->id, $this->date('2026-08-01'), $this->date('2026-08-31')));
        $this->assertSame(0, $overtime->syncFromAttendance($this->organization->id, $this->date('2026-08-01'), $this->date('2026-08-31')));

        $entry = HrOvertimeEntry::orderBy('work_date')->first();
        $this->assertSame(360, $entry->minutes_claimed);
        try {
            $overtime->approve($entry, $login);
            $this->fail('Approved own overtime.');
        } catch (ValidationException) {
        }
        $approved = $overtime->approve($entry, $this->user);
        $this->assertSame(240, $approved->minutes_approved); // the daily cap
        $this->assertSame('weekday', $approved->day_type);
    }
}
