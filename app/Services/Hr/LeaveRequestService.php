<?php

namespace App\Services\Hr;

use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrLeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asking for leave and the approval chain (S06): the manager first; types that need it also go to HR. Days are counted as working days (weekly off and
 * holidays do not use up leave). Approval takes the days out of the balance; cancelling puts them back; the attendance record follows.
 */
class LeaveRequestService
{
    public function __construct(private readonly LeaveBalanceService $balances, private readonly WorkCalendar $calendar, private readonly AttendanceService $attendance) {}

    public function request(HrEmployee $employee, HrLeaveType $type, CarbonInterface $from, CarbonInterface $to, bool $halfDay = false, ?string $reason = null, ?int $documentFileId = null): HrLeaveRequest
    {
        if ($type->organization_id !== $employee->organization_id || $type->status !== 'active') {
            throw ValidationException::withMessages(['leave_type' => 'That leave type is not available.']);
        }
        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to_date' => 'The last day is before the first.']);
        }
        if ($halfDay && ! $from->isSameDay($to)) {
            throw ValidationException::withMessages(['half_day' => 'A half day is a single day.']);
        }
        if (! in_array($employee->status, ['active', 'on_leave'], true)) {
            throw ValidationException::withMessages(['employee' => 'Only a working employee can take leave.']);
        }
        if ($type->gender_restriction && $type->gender_restriction !== $employee->gender) {
            throw ValidationException::withMessages(['leave_type' => 'That leave is not available for this person.']);
        }
        $days = $halfDay ? 0.5 : $this->calendar->workingDays($employee, $from, $to);
        if ($days <= 0) {
            throw ValidationException::withMessages(['from_date' => 'Those days are all days off already.']);
        }
        if ($type->max_consecutive_days && $days > $type->max_consecutive_days) {
            throw ValidationException::withMessages(['to_date' => "{$type->name} is limited to {$type->max_consecutive_days} days at a time."]);
        }
        if ($type->document_after_days && $days > $type->document_after_days && ! $documentFileId) {
            throw ValidationException::withMessages(['document' => 'Attach the supporting document (for example a medical certificate).']);
        }
        if (HrLeaveRequest::query()->where('employee_id', $employee->id)->whereIn('status', ['submitted', 'manager_approved', 'approved'])->whereDate('from_date', '<=', $to->toDateString())->whereDate('to_date', '>=', $from->toDateString())->exists()) {
            throw ValidationException::withMessages(['from_date' => 'You already have leave in those days.']);
        }
        if (HrAttendanceDay::query()->where(['employee_id' => $employee->id, 'locked' => true])->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())->exists()) {
            throw ValidationException::withMessages(['from_date' => 'That month is closed for payroll; ask HR to record it.']);
        }
        if ($type->annual_days > 0 && bccomp($this->balances->balance($employee, $type), (string) $days, 2) < 0) {
            throw ValidationException::withMessages(['days' => 'Not enough balance: '.$this->balances->balance($employee, $type)." day(s) left, $days asked for."]);
        }

        return HrLeaveRequest::create([
            'organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'leave_type_id' => $type->id, 'from_date' => $from->toDateString(), 'to_date' => $to->toDateString(),
            'days' => $days, 'half_day' => $halfDay, 'reason' => $reason, 'document_file_id' => $documentFileId,
        ]);
    }

    /** The manager's decision (or HR's, when the person has no manager). */
    public function decideAsManager(HrLeaveRequest $request, User $by, bool $approve, ?string $note = null): HrLeaveRequest
    {
        return $this->decide($request, $by, $approve, $note, 'manager');
    }

    public function decideAsHr(HrLeaveRequest $request, User $by, bool $approve, ?string $note = null): HrLeaveRequest
    {
        return $this->decide($request, $by, $approve, $note, 'hr');
    }

    private function decide(HrLeaveRequest $request, User $by, bool $approve, ?string $note, string $as): HrLeaveRequest
    {
        return DB::transaction(function () use ($request, $by, $approve, $note, $as) {
            $request = HrLeaveRequest::query()->lockForUpdate()->findOrFail($request->id);
            $employee = HrEmployee::findOrFail($request->employee_id);
            $type = HrLeaveType::findOrFail($request->leave_type_id);
            if ($employee->user_id === $by->id) {
                throw ValidationException::withMessages(['approval' => 'You cannot decide your own leave.']);
            }
            $expected = $as === 'manager' ? 'submitted' : ($type->requires_hr_approval || ! $employee->manager_id ? ['submitted', 'manager_approved'] : []);
            if (! in_array($request->status, (array) $expected, true)) {
                throw ValidationException::withMessages(['status' => "A request that is {$request->status} cannot be decided that way."]);
            }
            if ($as === 'manager' && $employee->manager_id && HrEmployee::find($employee->manager_id)?->user_id !== $by->id) {
                throw ValidationException::withMessages(['approval' => 'Only this person\'s manager can decide first.']);
            }
            if (! $approve) {
                $request->update(['status' => 'rejected', 'decision_note' => $note] + ($as === 'manager' ? ['manager_decided_by' => $by->id, 'manager_decided_at' => now()] : ['hr_decided_by' => $by->id, 'hr_decided_at' => now()]));

                return $request->refresh();
            }
            $final = $as === 'hr' || ! $type->requires_hr_approval;
            $stamp = $as === 'manager' ? ['manager_decided_by' => $by->id, 'manager_decided_at' => now()] : ['hr_decided_by' => $by->id, 'hr_decided_at' => now()];
            $request->update($stamp + ['status' => $final ? 'approved' : 'manager_approved', 'decision_note' => $note]);
            $final && $this->take($request, $employee, $type, $by);

            return $request->refresh();
        });
    }

    /** The days leave the balance and the attendance records follow. */
    private function take(HrLeaveRequest $request, HrEmployee $employee, HrLeaveType $type, User $by): void
    {
        $this->balances->credit($employee, $type, bcmul((string) $request->days, '-1', 2), 'taken', $request->from_date, 'LEAVE:'.$request->id, 'Leave '.$request->from_date->toDateString(), $by, HrLeaveRequest::class, $request->id);
        $this->recalc($request, $employee);
    }

    public function cancel(HrLeaveRequest $request, User $by): HrLeaveRequest
    {
        return DB::transaction(function () use ($request, $by) {
            $request = HrLeaveRequest::query()->lockForUpdate()->findOrFail($request->id);
            $employee = HrEmployee::findOrFail($request->employee_id);
            if (! in_array($request->status, ['submitted', 'manager_approved', 'approved'], true)) {
                throw ValidationException::withMessages(['status' => "A request that is {$request->status} cannot be cancelled."]);
            }
            if ($request->status === 'approved' && $request->from_date->lt(now()->startOfDay())) {
                throw ValidationException::withMessages(['status' => 'Leave that has started cannot be cancelled; ask HR to correct it.']);
            }
            $wasApproved = $request->status === 'approved';
            $request->update(['status' => 'cancelled']);
            if ($wasApproved) {
                $type = HrLeaveType::findOrFail($request->leave_type_id);
                $this->balances->credit($employee, $type, (string) $request->days, 'reversal', now(), 'LEAVE-REV:'.$request->id, 'Leave cancelled', $by, HrLeaveRequest::class, $request->id);
                $this->recalc($request, $employee);
            }

            return $request->refresh();
        });
    }

    private function recalc(HrLeaveRequest $request, HrEmployee $employee): void
    {
        for ($d = CarbonImmutable::parse($request->from_date->toDateString()); $d->lte($request->to_date); $d = $d->addDay()) {
            $this->attendance->calculateDay($employee, $d);
        }
    }
}
