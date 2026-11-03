<?php

namespace App\Services\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrEmployeeHistory;
use App\Models\Hr\HrJob;
use App\Models\Hr\HrPosition;
use App\Models\Provider;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The employee master (S01). HR is the master for who a person is, what they do, where they report and whether they still work here.
 * Anything that changes the job, department, manager, category or status leaves a history row that is never edited.
 */
class EmployeeService
{
    /** Fields whose change is history. */
    private const TRACKED = ['department_id' => 'transfer', 'branch_id' => 'transfer', 'job_id' => 'promotion', 'position_id' => 'job', 'manager_id' => 'manager', 'category' => 'category'];

    /** from => allowed next */
    private const TRANSITIONS = [
        'onboarding' => ['active', 'terminated'],
        'active' => ['on_leave', 'suspended', 'resigned', 'terminated', 'retired'],
        'on_leave' => ['active', 'resigned', 'terminated', 'retired'],
        'suspended' => ['active', 'terminated', 'resigned'],
        'resigned' => [], 'terminated' => [], 'retired' => [],
    ];

    public function __construct(private readonly HrNumbers $numbers, private readonly HrPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param  array<string, mixed>  $data */
    public function create(User $by, int $organizationId, array $data): HrEmployee
    {
        $this->validate($organizationId, $data);

        return DB::transaction(function () use ($by, $organizationId, $data) {
            $employee = HrEmployee::create($data + ['organization_id' => $organizationId, 'employee_no' => $this->numbers->employee($organizationId, $by), 'status' => 'onboarding', 'category' => 'permanent', 'weekly_off' => $this->policy->get('weekly_off')]);
            $this->history($employee, 'hired', null, ['job_id' => $employee->job_id, 'department_id' => $employee->department_id, 'category' => $employee->category], $employee->joined_on?->toDateString() ?? now()->toDateString(), 'Added to the register', $by);

            return $employee->refresh();
        });
    }

    /** @param  array<string, mixed>  $changes */
    public function update(HrEmployee $employee, array $changes, User $by, ?string $reason = null): HrEmployee
    {
        $this->validate($employee->organization_id, $changes + ['employee_no' => $employee->employee_no], $employee);
        unset($changes['employee_no'], $changes['status'], $changes['public_id'], $changes['organization_id']); // those have their own doors

        return DB::transaction(function () use ($employee, $changes, $by, $reason) {
            $employee = HrEmployee::query()->lockForUpdate()->findOrFail($employee->id);
            foreach (self::TRACKED as $field => $type) {
                if (array_key_exists($field, $changes) && (string) $employee->getAttribute($field) !== (string) $changes[$field]) {
                    $this->history($employee, $type, [$field => $employee->getAttribute($field)], [$field => $changes[$field]], now()->toDateString(), $reason, $by);
                }
            }
            $employee->update($changes);
            if (array_intersect(array_keys($changes), ['national_id', 'tin', 'bank_account', 'mobile_wallet', 'bank_name'])) {
                $this->audit->log('HR_SENSITIVE_UPDATED', HrEmployee::class, $employee->id, null, ['fields' => array_values(array_intersect(array_keys($changes), ['national_id', 'tin', 'bank_account', 'mobile_wallet', 'bank_name']))]);
            }

            return $employee->refresh();
        });
    }

    /** Moves a person to a new status. Separations record a date and reason; use `separate()` for those. */
    public function changeStatus(HrEmployee $employee, string $to, User $by, ?string $reason = null, ?CarbonInterface $on = null): HrEmployee
    {
        if (! in_array($to, self::TRANSITIONS[$employee->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "A person who is {$employee->status} cannot become $to."]);
        }
        if (in_array($to, ['resigned', 'terminated', 'retired'], true)) {
            return $this->separate($employee, $to, $on ?? now(), (string) ($reason ?? ''), $by);
        }
        if ($to === 'active' && $employee->status === 'onboarding') {
            $open = $employee->checklists()->where('kind', 'onboarding')->where('status', 'open')->exists();
            if ($open) {
                throw ValidationException::withMessages(['status' => 'Finish the onboarding checklist before making this person active.']);
            }
        }

        return DB::transaction(function () use ($employee, $to, $by, $reason, $on) {
            $from = $employee->status;
            $employee->update(['status' => $to] + ($to === 'active' && ! $employee->joined_on ? ['joined_on' => ($on ?? now())->toDateString()] : []));
            $this->history($employee, 'status', ['status' => $from], ['status' => $to], ($on ?? now())->toDateString(), $reason, $by);

            return $employee->refresh();
        });
    }

    public function separate(HrEmployee $employee, string $type, CarbonInterface $on, string $reason, User $by): HrEmployee
    {
        if (! in_array($type, ['resigned', 'terminated', 'retired'], true)) {
            throw ValidationException::withMessages(['status' => 'Use resigned, terminated or retired.']);
        }
        if (! in_array($type, self::TRANSITIONS[$employee->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "A person who is {$employee->status} cannot become $type."]);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why the person is leaving.']);
        }
        if ($employee->joined_on && $on->lt($employee->joined_on)) {
            throw ValidationException::withMessages(['separated_on' => 'The last day is before the joining day.']);
        }

        return DB::transaction(function () use ($employee, $type, $on, $reason, $by) {
            $from = $employee->status;
            $employee->update(['status' => $type, 'separated_on' => $on->toDateString(), 'separation_reason' => mb_substr($reason, 0, 300)]);
            $this->history($employee, 'separation', ['status' => $from], ['status' => $type, 'separated_on' => $on->toDateString()], $on->toDateString(), $reason, $by);
            // The person stops being a provider the day they leave; HR is the master for status (D-12).
            Provider::query()->where('employee_id', $employee->id)->update(['status' => 'inactive']);

            return $employee->refresh();
        });
    }

    /** A person comes back: the same record, a new stretch of service, and a visible history row. */
    public function rehire(HrEmployee $employee, CarbonInterface $on, User $by, ?string $reason = null): HrEmployee
    {
        if (! in_array($employee->status, ['resigned', 'terminated', 'retired'], true)) {
            throw ValidationException::withMessages(['status' => 'Only a person who has left can be re-hired.']);
        }

        return DB::transaction(function () use ($employee, $on, $by, $reason) {
            $from = $employee->status;
            $employee->update(['status' => 'onboarding', 'joined_on' => $on->toDateString(), 'separated_on' => null, 'separation_reason' => null]);
            $this->history($employee, 'rehire', ['status' => $from], ['status' => 'onboarding'], $on->toDateString(), $reason, $by);

            return $employee->refresh();
        });
    }

    public function linkUser(HrEmployee $employee, User $user): HrEmployee
    {
        if (HrEmployee::query()->where('user_id', $user->id)->where('id', '!=', $employee->id)->exists()) {
            throw ValidationException::withMessages(['user' => 'That login already belongs to another employee.']);
        }
        if (! $user->organizations()->where('organizations.id', $employee->organization_id)->exists()) {
            throw ValidationException::withMessages(['user' => 'That login is not in this organization.']);
        }
        $employee->update(['user_id' => $user->id]);

        return $employee->refresh();
    }

    /** @return array{filled: int, authorized: int} headcount against the position */
    public function headcount(HrPosition $position): array
    {
        return ['filled' => HrEmployee::query()->where('position_id', $position->id)->whereIn('status', ['onboarding', 'active', 'on_leave', 'suspended'])->count(), 'authorized' => $position->headcount];
    }

    /** @param  array<string, mixed>|null  $from @param  array<string, mixed>|null  $to */
    private function history(HrEmployee $employee, string $type, ?array $from, ?array $to, string $effective, ?string $reason, ?User $by): void
    {
        HrEmployeeHistory::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'change_type' => $type, 'from_value' => $from, 'to_value' => $to, 'effective_on' => $effective, 'reason' => $reason, 'changed_by' => $by?->id, 'created_at' => now()]);
    }

    /** @param  array<string, mixed>  $data */
    private function validate(int $organizationId, array $data, ?HrEmployee $existing = null): void
    {
        if (isset($data['category']) && ! in_array($data['category'], (array) $this->policy->get('employee_categories'), true)) {
            throw ValidationException::withMessages(['category' => 'Not a known category.']);
        }
        if (! empty($data['device_code']) && HrEmployee::query()->where('organization_id', $organizationId)->where('device_code', $data['device_code'])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
            throw ValidationException::withMessages(['device_code' => 'That attendance-device number belongs to another person.']);
        }
        if (! empty($data['user_id'])) {
            $user = User::query()->find($data['user_id']);
            if (! $user || ! $user->organizations()->where('organizations.id', $organizationId)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'That login is not in this organization.']);
            }
            if (HrEmployee::query()->where('user_id', $data['user_id'])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                throw ValidationException::withMessages(['user_id' => 'That login already belongs to another employee.']);
            }
        }
        if (! empty($data['manager_id'])) {
            $manager = HrEmployee::query()->where('organization_id', $organizationId)->find($data['manager_id']);
            if (! $manager) {
                throw ValidationException::withMessages(['manager_id' => 'That manager is not in this organization.']);
            }
            if ($existing && ($manager->id === $existing->id || $this->reportsTo($manager, $existing))) {
                throw ValidationException::withMessages(['manager_id' => 'A person cannot report to themselves, directly or through their own team.']);
            }
        }
        foreach (['job_id' => HrJob::class, 'position_id' => HrPosition::class] as $field => $class) {
            if (! empty($data[$field]) && ! $class::query()->where('organization_id', $organizationId)->whereKey($data[$field])->exists()) {
                throw ValidationException::withMessages([$field => 'That does not exist in this organization.']);
            }
        }
        if (! empty($data['date_of_birth']) && $data['date_of_birth'] > now()->subYears(14)->toDateString()) {
            throw ValidationException::withMessages(['date_of_birth' => 'An employee must be at least 14 years old.']);
        }
    }

    private function reportsTo(HrEmployee $candidate, HrEmployee $boss): bool
    {
        for ($i = 0; $i < 20 && $candidate->manager_id; $i++) {
            if ($candidate->manager_id === $boss->id) {
                return true;
            }
            $candidate = HrEmployee::find($candidate->manager_id) ?? $candidate;
            if (! $candidate->manager_id) {
                break;
            }
        }

        return false;
    }
}
