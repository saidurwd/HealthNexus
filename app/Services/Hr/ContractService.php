<?php

namespace App\Services\Hr;

use App\Models\Hr\HrContract;
use App\Models\Hr\HrEmployee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Contracts are never edited: a renewal or a change is a new contract that supersedes the old one (S02). */
class ContractService
{
    public function start(HrEmployee $employee, string $type, string $startOn, ?string $endOn, User $by, int $noticeDays = 30, int $probationMonths = 0, ?string $terms = null): HrContract
    {
        if (! in_array($type, ['permanent', 'fixed_term', 'probation', 'visiting'], true)) {
            throw ValidationException::withMessages(['contract_type' => 'Not a known contract type.']);
        }
        if ($endOn && $endOn <= $startOn) {
            throw ValidationException::withMessages(['end_on' => 'The contract must end after it starts.']);
        }
        if (in_array($type, ['fixed_term', 'visiting'], true) && ! $endOn) {
            throw ValidationException::withMessages(['end_on' => 'A fixed-term contract needs an end date.']);
        }

        return DB::transaction(function () use ($employee, $type, $startOn, $endOn, $by, $noticeDays, $probationMonths, $terms) {
            $current = HrContract::query()->where(['employee_id' => $employee->id, 'status' => 'active'])->lockForUpdate()->first();
            if ($current) {
                if ($startOn <= $current->start_on->toDateString()) {
                    throw ValidationException::withMessages(['start_on' => 'The new contract must start after the current one.']);
                }
                $current->update(['status' => 'renewed', 'end_on' => $current->end_on && $current->end_on->toDateString() < $startOn ? $current->end_on : date('Y-m-d', strtotime($startOn.' -1 day'))]);
            }
            $contract = HrContract::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'contract_type' => $type, 'start_on' => $startOn, 'end_on' => $endOn, 'notice_days' => $noticeDays, 'probation_months' => $probationMonths, 'terms' => $terms, 'supersedes_id' => $current?->id, 'created_by' => $by->id]);
            if ($probationMonths > 0) {
                $employee->update(['probation_ends_on' => date('Y-m-d', strtotime($startOn." +$probationMonths months"))]);
            }

            return $contract;
        });
    }

    /** Active contracts that end within the given days. @return \Illuminate\Support\Collection<int, HrContract> */
    public function expiring(int $organizationId, int $days)
    {
        return HrContract::query()->with('employee')->where('organization_id', $organizationId)->where('status', 'active')->whereNotNull('end_on')
            ->whereDate('end_on', '>=', now()->toDateString())->whereDate('end_on', '<=', now()->addDays($days)->toDateString())->orderBy('end_on')->get();
    }
}
