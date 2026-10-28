<?php

namespace App\Services\Hr;

use App\Models\Hr\HrPayrollRun;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** The people numbers (S12). Salary figures are only produced for callers who hold the payroll permissions (the controller decides); headcount and attendance are not salary data. */
class HrReportService
{
    /** @return array<string, mixed> */
    public function dashboard(int $organizationId): array
    {
        $today = now()->toDateString();
        $employees = DB::table('hr_employees')->where('organization_id', $organizationId);
        $lastRun = HrPayrollRun::query()->where(['organization_id' => $organizationId, 'run_type' => 'regular'])->where('status', '!=', 'cancelled')->latest('id')->first();

        return [
            'headcount' => (clone $employees)->whereIn('status', ['active', 'on_leave'])->count(),
            'onboarding' => (clone $employees)->where('status', 'onboarding')->count(),
            'joined_30' => (clone $employees)->whereDate('joined_on', '>=', now()->subDays(30)->toDateString())->count(),
            'left_30' => (clone $employees)->whereDate('separated_on', '>=', now()->subDays(30)->toDateString())->count(),
            'by_category' => (clone $employees)->whereIn('status', ['active', 'on_leave'])->selectRaw('category, count(*) c')->groupBy('category')->pluck('c', 'category')->all(),
            'on_leave_today' => DB::table('hr_attendance_days')->where('organization_id', $organizationId)->whereDate('work_date', $today)->where('status', 'leave')->count(),
            'absent_today' => DB::table('hr_attendance_days')->where('organization_id', $organizationId)->whereDate('work_date', $today)->where('status', 'absent')->count(),
            'leave_to_approve' => DB::table('hr_leave_requests')->where('organization_id', $organizationId)->whereIn('status', ['submitted', 'manager_approved'])->count(),
            'corrections_to_decide' => DB::table('hr_regularizations')->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'overtime_to_approve' => DB::table('hr_overtime_entries')->where('organization_id', $organizationId)->where('status', 'pending')->count(),
            'credentials_expired' => DB::table('hr_credentials as c')->join('hr_employees as e', 'e.id', '=', 'c.employee_id')->where('c.organization_id', $organizationId)->where('c.status', 'expired')->whereIn('e.status', ['active', 'on_leave'])->count(),
            'credentials_expiring_30' => DB::table('hr_credentials')->where('organization_id', $organizationId)->where('status', 'active')->whereBetween('expires_on', [$today, now()->addDays(30)->toDateString()])->count(),
            'contracts_ending_30' => DB::table('hr_contracts')->where('organization_id', $organizationId)->where('status', 'active')->whereBetween('end_on', [$today, now()->addDays(30)->toDateString()])->count(),
            'unmapped_punches' => DB::table('hr_punches')->where('organization_id', $organizationId)->whereNull('employee_id')->count(),
            'provider_links_pending' => DB::table('hr_provider_links')->where('organization_id', $organizationId)->where('status', 'pending')->count(),
            'open_vacancies' => DB::table('hr_vacancies')->where('organization_id', $organizationId)->where('status', 'open')->count(),
            'last_payroll' => $lastRun ? ['number' => $lastRun->number, 'status' => $lastRun->status, 'employees' => $lastRun->employee_count] : null,
        ];
    }

    /** @return Collection<int, \stdClass> */
    public function headcountByDepartment(int $organizationId)
    {
        return DB::table('hr_employees as e')->leftJoin('departments as d', 'd.id', '=', 'e.department_id')->where('e.organization_id', $organizationId)->whereIn('e.status', ['active', 'on_leave'])
            ->groupBy('d.id', 'd.name')->selectRaw("COALESCE(d.name, 'No department') as department, count(*) as headcount")->orderByDesc('headcount')->get();
    }

    /** Joiners and leavers per month for a year. @return list<array{month: string, joined: int, left: int}> */
    public function turnover(int $organizationId, int $year): array
    {
        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = sprintf('%04d-%02d-01', $year, $m);
            $end = date('Y-m-t', strtotime($start));
            $out[] = [
                'month' => date('M', strtotime($start)),
                'joined' => DB::table('hr_employees')->where('organization_id', $organizationId)->whereBetween('joined_on', [$start, $end])->count(),
                'left' => DB::table('hr_employees')->where('organization_id', $organizationId)->whereBetween('separated_on', [$start, $end])->count(),
            ];
        }

        return $out;
    }

    /** Share of working days lost to absence in a month, per department. @return \Illuminate\Support\Collection<int, object> */
    public function absenteeism(int $organizationId, CarbonInterface $month)
    {
        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        return DB::table('hr_attendance_days as a')->join('hr_employees as e', 'e.id', '=', 'a.employee_id')->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('a.organization_id', $organizationId)->whereBetween('a.work_date', [$from, $to])->whereIn('a.status', ['present', 'late', 'half_day', 'absent', 'leave'])
            ->groupBy('d.id', 'd.name')->selectRaw("COALESCE(d.name, 'No department') as department, count(*) as working_days, SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days, SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days, SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) as leave_days")
            ->orderBy('department')->get();
    }

    /** Payroll cost per approved regular run. @return \Illuminate\Support\Collection<int, object> */
    public function payrollCost(int $organizationId)
    {
        return DB::table('hr_payroll_runs as r')->join('hr_payroll_periods as p', 'p.id', '=', 'r.period_id')->where('r.organization_id', $organizationId)->whereIn('r.status', ['approved', 'paid'])
            ->orderBy('p.month_start')->get(['p.month_start', 'r.number', 'r.run_type', 'r.employee_count', 'r.total_gross', 'r.total_deductions', 'r.total_tax', 'r.total_net', 'r.total_employer']);
    }

    /** Licence compliance of people who need one. @return \Illuminate\Support\Collection<int, object> */
    public function credentialCompliance(int $organizationId)
    {
        return DB::table('hr_credentials as c')->join('hr_employees as e', 'e.id', '=', 'c.employee_id')->leftJoin('departments as d', 'd.id', '=', 'e.department_id')->where('c.organization_id', $organizationId)->whereIn('e.status', ['active', 'on_leave'])
            ->groupBy('d.id', 'd.name')->selectRaw("COALESCE(d.name, 'No department') as department, count(*) as total, SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as valid, SUM(CASE WHEN c.status = 'expired' THEN 1 ELSE 0 END) as expired")->orderBy('department')->get();
    }

    /** Approved overtime minutes per department and month. @return \Illuminate\Support\Collection<int, object> */
    public function overtime(int $organizationId, CarbonInterface $from, CarbonInterface $to)
    {
        return DB::table('hr_overtime_entries as o')->join('hr_employees as e', 'e.id', '=', 'o.employee_id')->leftJoin('departments as d', 'd.id', '=', 'e.department_id')->where('o.organization_id', $organizationId)->whereIn('o.status', ['approved', 'paid'])
            ->whereBetween('o.work_date', [$from->toDateString(), $to->toDateString()])->groupBy('d.id', 'd.name')->selectRaw("COALESCE(d.name, 'No department') as department, SUM(o.minutes_approved) / 60 as hours, count(*) as entries")->orderByDesc('hours')->get();
    }
}
