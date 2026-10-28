<?php

namespace Tests\Feature\Demo;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Hr\HrPayrollRun;
use App\Models\Organization;
use App\Services\Hr\PayrollService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The three-month people-and-payroll history on its own (the full history runs in DemoHistoryTest, one month at a time). */
class HrHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_three_months_of_people_and_payroll_hang_together(): void
    {
        $organization = Organization::factory()->create(['code' => 'HRH']);
        Branch::factory()->create(['organization_id' => $organization->id]);
        foreach (['Surgery', 'Medicine', 'Casualty', 'Pathology', 'Administration'] as $name) {
            Department::factory()->create(['organization_id' => $organization->id, 'name' => $name]);
        }
        $this->seed([PermissionSeeder::class, SettingsTableSeeder::class]);

        $code = Artisan::call('demo:history', ['organization' => 'HRH', '--only' => ['HrHistory'], '--password' => 'demo-pass-123', '--months' => 3]);
        $output = Artisan::output();
        $this->assertSame(0, $code, $output);
        getenv('DEMO_VERBOSE') && fwrite(STDERR, $output);
        $org = $organization->id;

        $this->assertStringContainsString('People and payroll', $output);
        $has = fn (string $table, int $min, array $where = []) => $this->assertGreaterThanOrEqual($min, DB::table($table)->where('organization_id', $org)->where($where)->count(), "$table has history");
        $has('hr_employees', 40);
        $has('hr_employee_history', 40);
        $has('hr_contracts', 40);
        $has('hr_credentials', 25);
        $has('hr_roster_entries', 1500);
        $has('hr_punches', 1500);
        $has('hr_attendance_days', 1500);
        $has('hr_leave_requests', 10, ['status' => 'approved']);
        $has('hr_leave_ledger', 80);
        $has('hr_regularizations', 1);
        $has('hr_overtime_entries', 1);
        $has('hr_payroll_runs', 2, ['status' => 'paid']);
        $has('hr_payroll_lines', 80);
        $has('hr_payment_batches', 2);
        $has('hr_appraisals', 20);
        $has('hr_vacancies', 1);

        // the story
        $this->assertGreaterThanOrEqual(1, DB::table('hr_employees')->where('organization_id', $org)->whereIn('status', ['resigned', 'terminated'])->count(), 'someone left');
        $this->assertGreaterThanOrEqual(1, DB::table('hr_payroll_runs')->where('organization_id', $org)->where('run_type', 'supplementary')->where('status', 'approved')->count(), 'a correction run was paid');
        $this->assertGreaterThanOrEqual(1, DB::table('hr_attendance_days')->where('organization_id', $org)->where('status', 'absent')->count());
        $this->assertGreaterThanOrEqual(1, DB::table('hr_attendance_days')->where('organization_id', $org)->where('status', 'late')->count());

        // and it adds up
        foreach (HrPayrollRun::where('organization_id', $org)->whereIn('status', ['approved', 'paid'])->get() as $run) {
            $this->assertNotContains(false, app(PayrollService::class)->reconcile($run), "run {$run->number} reconciles");
            $this->assertNotSame($run->created_by, $run->approved_by, 'a second person approved '.$run->number);
        }
        $this->assertSame(0, DB::table('hr_attendance_days as a')->join('hr_payroll_periods as p', function ($j) {
            $j->on('p.organization_id', '=', 'a.organization_id');
        })->where('p.status', 'closed')->whereColumn('a.work_date', '>=', 'p.month_start')->whereColumn('a.work_date', '<=', 'p.month_end')->where('a.locked', false)->count(), 'every day of a closed month is locked');
        $this->assertSame(0, DB::table('hr_employees')->where('organization_id', $org)->where('status', 'active')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('hr_salary_structures as s')->whereColumn('s.employee_id', 'hr_employees.id'))->count(), 'every active employee has a salary structure');
        $this->assertSame(0, DB::table('users as u')->join('hr_employees as e', 'e.user_id', '=', 'u.id')->whereIn('e.status', ['resigned', 'terminated'])->whereNotNull('e.separated_on')->where('u.is_active', true)->whereExists(fn ($q) => $q->selectRaw('1')->from('hr_checklists as c')->whereColumn('c.employee_id', 'e.id')->where('c.kind', 'offboarding')->where('c.status', 'done'))->count(), 'offboarding switched logins off');
        $this->assertSame(0, DB::table('hr_leave_ledger')->where('organization_id', $org)->where('entry_type', 'taken')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('hr_leave_requests as r')->whereColumn('r.id', 'hr_leave_ledger.ref_id')->where('r.status', 'approved'))->count(), 'leave taken only for approved requests');
        $this->assertSame(0, DB::table('hr_employees')->where('organization_id', $org)->whereNotNull('national_id')->pluck('national_id')->filter(fn ($raw) => preg_match('/^[0-9]{13}$/', $raw) === 1)->count(), 'national ids are not stored in clear text');
    }
}
