<?php

namespace App\Services\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrPayrollLine;
use Illuminate\Support\Collection;

/** Payslips (S10/S11). A payslip is an approved, published run line; a person can only reach their own (the caller passes the employee tied to the login). */
class PayslipService
{
    /** @return Collection<int, HrPayrollLine> published payslips of one employee, newest first */
    public function forEmployee(HrEmployee $employee): Collection
    {
        return HrPayrollLine::query()->with(['run.period', 'items'])->where('employee_id', $employee->id)
            ->whereHas('run', fn ($q) => $q->whereIn('status', ['approved', 'paid'])->whereNotNull('payslips_published_at'))->orderByDesc('id')->get();
    }

    public function find(HrEmployee $employee, int $lineId): ?HrPayrollLine
    {
        return $this->forEmployee($employee)->firstWhere('id', $lineId);
    }
}
