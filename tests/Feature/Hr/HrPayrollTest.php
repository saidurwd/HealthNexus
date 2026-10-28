<?php

namespace Tests\Feature\Hr;

use App\Contracts\Integration\FinanceGateway;
use App\Contracts\Integration\IntegrationResult;
use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrLoan;
use App\Models\Hr\HrOvertimeEntry;
use App\Models\Hr\HrPayrollAdjustment;
use App\Models\Hr\HrPayrollLine;
use App\Models\Hr\HrPayrollPeriod;
use App\Models\Hr\HrPayrollRun;
use App\Models\Hr\HrSalaryStructure;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\CompensationService;
use App\Services\Hr\PaymentFileService;
use App\Services\Hr\PayrollJournalService;
use App\Services\Hr\PayrollService;
use App\Services\Hr\PayslipService;
use App\Services\Hr\RosterService;
use Illuminate\Validation\ValidationException;

class HrPayrollTest extends HrTestCase
{
    // August 2026: 31 days, 1 Aug is a Saturday. "Today" in these tests is 10 Sept 2026, so August is a finished month.

    private function august(): HrPayrollPeriod
    {
        return app(PayrollService::class)->openPeriod($this->organization->id, $this->date('2026-08-15'));
    }

    private function process(): void
    {
        app(AttendanceService::class)->processRange($this->organization->id, $this->date('2026-08-01'), $this->date('2026-08-31'));
    }

    private function line(HrPayrollRun $run, $employee): HrPayrollLine
    {
        return $run->lines()->where('employee_id', $employee->id)->firstOrFail()->load('items');
    }

    private function item(HrPayrollLine $line, string $code): ?string
    {
        $item = $line->items->firstWhere('code', $code);

        return $item ? (string) $item->amount : null;
    }

    public function test_a_full_month_is_paid_exactly_by_the_structure_with_pf_and_tax_from_the_slabs(): void
    {
        $employee = $this->employee();
        $this->salary($employee);
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));
        $line = $this->line($run, $employee);

        $this->assertSame('30000.00', $this->item($line, 'BASIC'));
        $this->assertSame('15000.00', $this->item($line, 'HRENT'));
        $this->assertSame('1500.00', $this->item($line, 'MEDICAL'));
        $this->assertSame('46500.00', (string) $line->gross);
        $this->assertSame('3000.00', $this->item($line, 'PF'));
        $this->assertSame('3000.00', $this->item($line, 'PFER'));
        // taxable 45,000 a month = 540,000 a year: 0% to 350k, 5% to 450k (5,000), 10% of the last 90k (9,000) = 14,000 a year
        $this->assertSame('1166.67', $this->item($line, 'TAX'));
        $this->assertSame('42333.33', (string) $line->net);
        $this->assertSame('3000.00', (string) $line->employer);
        $this->assertSame(31, $line->inputs['paid_days']);
    }

    public function test_absent_days_reduce_pay_in_proportion(): void
    {
        $employee = $this->employee();
        $this->salary($employee);
        $shift = $this->shift('DAY');
        $roster = app(RosterService::class);
        $roster->assign($employee, $this->date('2026-08-04'), $shift, $this->user);
        $roster->assign($employee, $this->date('2026-08-05'), $shift, $this->user); // rostered, never came: two absent days
        $this->process();
        $payroll = app(PayrollService::class);
        $line = $this->line($payroll->calculate($payroll->createRun($this->user, $this->august())), $employee);

        $this->assertSame(29, $line->inputs['paid_days']);
        $this->assertSame('28064.52', $this->item($line, 'BASIC')); // 30000 x 29 / 31
        $this->assertSame('14032.26', $this->item($line, 'HRENT'));
        $this->assertSame('1403.23', $this->item($line, 'MEDICAL'));
        $this->assertSame('3000.00', $this->item($line, 'PF'));   // not reduced
        $this->assertSame('43500.01', (string) $line->gross);
        $this->assertSame('876.34', $this->item($line, 'TAX'));
        $this->assertSame('39623.67', (string) $line->net);
    }

    public function test_a_person_who_joins_mid_month_is_paid_only_for_the_days_employed(): void
    {
        $employee = $this->employee(['joined_on' => '2026-08-16']);
        $this->salary($employee, '2026-08-01');
        $this->process();
        $payroll = app(PayrollService::class);
        $line = $this->line($payroll->calculate($payroll->createRun($this->user, $this->august())), $employee);

        $this->assertSame(16, $line->inputs['employed_days']);
        $this->assertSame('15483.87', $this->item($line, 'BASIC')); // 30000 x 16 / 31
    }

    public function test_approved_overtime_is_paid_at_the_hourly_rate_times_the_multiplier(): void
    {
        $employee = $this->employee();
        $this->salary($employee);
        HrOvertimeEntry::create(['organization_id' => $this->organization->id, 'employee_id' => $employee->id, 'work_date' => '2026-08-10', 'minutes_claimed' => 120, 'minutes_approved' => 120, 'day_type' => 'weekday', 'status' => 'approved']);
        $this->process();
        $payroll = app(PayrollService::class);
        $line = $this->line($payroll->calculate($payroll->createRun($this->user, $this->august())), $employee);

        $this->assertSame('576.92', $this->item($line, 'OT')); // 30000 / 208 per hour x 2 hours x 2.0
        $this->assertSame('47076.92', (string) $line->gross);
    }

    public function test_a_loan_instalment_is_taken_and_the_balance_falls_only_at_approval(): void
    {
        $employee = $this->employee();
        $this->salary($employee);
        $comp = app(CompensationService::class);
        $loan = $comp->requestLoan($employee, 'loan', '12000', '3000', $this->date('2026-08-01'));
        $comp->approveLoan($loan, $this->actor(['payroll.loan.manage']));
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));

        $this->assertSame('3000.00', $this->item($this->line($run, $employee), 'LOAN'));
        $this->assertSame('12000.00', (string) $loan->refresh()->balance);
        $payroll->approve($run, $this->actor(['payroll.run.approve']));
        $this->assertSame('9000.00', (string) $loan->refresh()->balance);
        $this->assertSame('active', $loan->status);
    }

    public function test_the_same_inputs_give_the_same_result_and_the_same_fingerprint(): void
    {
        $a = $this->employee();
        $b = $this->employee();
        $this->salary($a);
        $this->salary($b, '2025-01-01', '52000');
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));
        $hash = $run->input_hash;
        $net = (string) $run->total_net;

        $again = $payroll->calculate($run);
        $this->assertSame($hash, $again->input_hash);
        $this->assertSame($net, (string) $again->total_net);
        $this->assertSame(2, $again->calculation);

        $this->salary($a, '2026-09-01', '31000'); // changes September, not August
        $this->assertSame($hash, $payroll->calculate($again)->input_hash);
        HrAttendanceDay::where('employee_id', $a->id)->whereDate('work_date', '2026-08-12')->update(['status' => 'absent']);
        $this->assertNotSame($hash, $payroll->calculate($again)->input_hash); // an input changed, the fingerprint changed
    }

    public function test_approval_needs_a_second_person_locks_the_month_and_a_correction_is_a_supplementary_run(): void
    {
        $employee = $this->employee(['user_id' => $this->staff()->id]);
        $this->salary($employee);
        HrOvertimeEntry::create(['organization_id' => $this->organization->id, 'employee_id' => $employee->id, 'work_date' => '2026-08-10', 'minutes_claimed' => 60, 'minutes_approved' => 60, 'day_type' => 'weekday', 'status' => 'approved']);
        $this->process();
        $payroll = app(PayrollService::class);
        $creator = $this->actor(['payroll.run.create']);
        $run = $payroll->calculate($payroll->createRun($creator, $this->august()));

        try {
            $payroll->approve($run, $creator);
            $this->fail('The creator approved their own payroll.');
        } catch (ValidationException) {
        }
        $approved = $payroll->approve($run, $this->actor(['payroll.run.approve']));

        $this->assertSame('approved', $approved->status);
        $this->assertSame('closed', $this->august()->status);
        $this->assertSame(0, HrAttendanceDay::where('employee_id', $employee->id)->where('locked', false)->count());
        $this->assertSame('paid', HrOvertimeEntry::first()->status);
        try {
            $payroll->calculate($approved);
            $this->fail('An approved run was calculated again.');
        } catch (ValidationException) {
        }
        try {
            $payroll->createRun($creator, $this->august());
            $this->fail('A second regular run for the same month.');
        } catch (ValidationException) {
        }

        // the correction
        $payroll->addAdjustment($employee, $this->august(), 'earning', 'Arrears: grade change', '2500.00', 'Promotion backdated to August', $creator);
        $supp = $payroll->createRun($creator, $this->august(), 'supplementary', 'Arrears for August');
        $supp = $payroll->calculate($supp);
        $this->assertSame('2500.00', (string) $supp->total_net);
        $this->assertSame(1, $supp->employee_count);
        $payroll->approve($supp, $this->actor(['payroll.run.approve']));
        $this->assertSame($supp->id, HrPayrollAdjustment::first()->run_id);
        $this->assertSame('approved', $run->refresh()->status); // the regular run itself is untouched
        $this->assertNotEmpty($payroll->reconcile($supp->refresh()));
        $this->assertNotContains(false, $payroll->reconcile($supp));
        $this->assertNotContains(false, $payroll->reconcile($run->refresh()));
    }

    public function test_a_supplementary_run_needs_an_approved_month_and_a_reason(): void
    {
        $payroll = app(PayrollService::class);
        $this->expectException(ValidationException::class);
        $payroll->createRun($this->user, $this->august(), 'supplementary', 'Nothing to correct yet');
    }

    public function test_blocking_findings_stop_approval_and_warnings_do_not(): void
    {
        $withPay = $this->employee();
        $this->salary($withPay);
        $noPay = $this->employee(); // active, but nobody has given them a salary
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));

        $found = $payroll->exceptions($run);
        $this->assertArrayHasKey('no_structure', $found);
        $this->assertArrayHasKey('no_payment_account', $found);
        try {
            $payroll->approve($run, $this->actor(['payroll.run.approve']));
            $this->fail('Approved with a person who has no salary structure.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('no structure', $e->errors()['exceptions'][0]);
        }
        $noPay->update(['joined_on' => '2026-09-05']); // not employed in August after all
        $run = $payroll->calculate($run);
        $withPay->update(['bank_account' => '111', 'bank_name' => 'City Bank']);
        $this->assertSame('approved', $payroll->approve($payroll->calculate($run), $this->actor(['payroll.run.approve']))->status);
    }

    public function test_payment_files_split_bank_and_wallet_reconcile_with_the_run_and_audit_the_download(): void
    {
        $bank = $this->employee(['bank_account' => '0011223344', 'bank_name' => 'City Bank']);
        $wallet = $this->employee(['mobile_wallet' => '01711000000']);
        $both = $this->employee(['bank_account' => '5566', 'bank_name' => 'Metro Bank', 'mobile_wallet' => '01811000000']);
        foreach ([$bank, $wallet, $both] as $e) {
            $this->salary($e);
        }
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));
        $payroll->approve($run, $this->actor(['payroll.run.approve']));
        $files = app(PaymentFileService::class);

        $bankBatch = $files->prepare($run->refresh(), 'bank', $this->user);
        $walletBatch = $files->prepare($run, 'mobile_wallet', $this->user);
        $this->assertSame(2, $bankBatch->payee_count); // bank wins when a person has both
        $this->assertSame(1, $walletBatch->payee_count);
        $csv = $files->download($bankBatch, $this->user);
        $this->assertStringContainsString('0011223344', $csv);
        $this->assertStringNotContainsString('01711000000', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT_FILE_DOWNLOADED']);
        $this->assertNotContains(false, $payroll->reconcile($run));
        $this->assertSame((string) $run->total_net, bcadd((string) $bankBatch->total, (string) $walletBatch->total, 2));

        $this->expectException(ValidationException::class);
        $files->prepare($run, 'bank', $this->user); // not twice
    }

    public function test_the_journal_balances_and_posts_through_the_finance_gateway(): void
    {
        $e = $this->employee(['bank_account' => '1']);
        $this->salary($e);
        app(CompensationService::class)->requestLoan($e, 'advance', '5000', '1000', $this->date('2026-08-01'));
        app(CompensationService::class)->approveLoan(HrLoan::first(), $this->actor(['payroll.loan.manage']));
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));
        $run = $payroll->approve($run, $this->actor(['payroll.run.approve']));

        $lines = collect($run->journal['lines'])->keyBy('account');
        $this->assertSame('49500.00', $lines['SALARY_EXPENSE']['debit']); // 46,500 gross + 3,000 employer PF
        $this->assertSame((string) $run->total_net, $lines['SALARY_PAYABLE']['credit']);
        $this->assertSame('6000.00', $lines['PF_PAYABLE']['credit']);
        $this->assertSame('1000.00', $lines['LOAN_RECEIVABLE']['credit']);
        $this->assertFalse(app(PayrollJournalService::class)->post($run)); // the null adapter does not take it
        $this->assertNull($run->refresh()->finance_reference);

        app()->bind(FinanceGateway::class, fn () => new class implements FinanceGateway
        {
            public function postJournal(array $journal): IntegrationResult
            {
                return IntegrationResult::handled(['reference' => 'JV-1']);
            }

            public function postReceivable(array $receivable): IntegrationResult
            {
                return IntegrationResult::handled();
            }

            public function postPayable(array $payable): IntegrationResult
            {
                return IntegrationResult::handled();
            }
        });
        $this->assertTrue(app(PayrollJournalService::class)->post($run));
        $this->assertSame('JV-1', $run->refresh()->finance_reference);
    }

    public function test_tax_slabs_are_effective_dated_settings(): void
    {
        $employee = $this->employee();
        $this->salary($employee);
        $this->process();
        $payroll = app(PayrollService::class);
        app(CompensationService::class)->setSlabs($this->organization->id, $this->date('2026-07-01'), [[0, 600000, 0], [600000, null, 20]]);
        $line = $this->line($payroll->calculate($payroll->createRun($this->user, $this->august())), $employee);

        $this->assertNull($this->item($line, 'TAX')); // 540,000 a year is under the new zero-rate band

        $this->expectException(ValidationException::class);
        app(CompensationService::class)->setSlabs($this->organization->id, $this->date('2026-07-01'), [[0, 100, 0], [200, null, 10]]); // a gap
    }

    public function test_salary_structures_are_versions_and_a_paid_month_cannot_be_rewritten(): void
    {
        $employee = $this->employee();
        $this->salary($employee);
        $comp = app(CompensationService::class);
        $this->salary($employee, '2026-08-01', '34000');
        $this->assertSame(2, $employee->hasMany(HrSalaryStructure::class, 'employee_id')->count());
        $this->assertSame('2026-07-31', HrSalaryStructure::where('version', 1)->first()->effective_to->toDateString());
        $this->assertSame(34000, (int) $comp->structureOn($employee, $this->date('2026-08-20'))->lines->firstWhere('component.code', 'BASIC')->amount);
        $this->assertSame(30000, (int) $comp->structureOn($employee, $this->date('2026-07-20'))->lines->firstWhere('component.code', 'BASIC')->amount);

        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));
        $payroll->approve($run, $this->actor(['payroll.run.approve']));
        try {
            $this->salary($employee, '2026-08-20', '40000'); // inside a paid month
            $this->fail('A paid month was rewritten.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_from', $e->errors());
        }
        $this->salary($employee, '2026-09-01', '40000');
        $this->assertTrue(true);
    }

    public function test_a_structure_needs_a_basic_and_valid_components(): void
    {
        $employee = $this->employee();
        $comp = app(CompensationService::class);
        foreach ([
            [['component' => 'MEDICAL', 'amount' => 1000]],                       // no basic
            [['component' => 'BASIC', 'amount' => 0]],                            // basic is zero
            [['component' => 'BASIC', 'amount' => 100], ['component' => 'NOPE', 'amount' => 1]],
            [['component' => 'BASIC', 'amount' => 100], ['component' => 'HRENT', 'percent' => 150]],
        ] as $lines) {
            try {
                $comp->newStructure($employee, $this->date('2026-09-01'), $lines, $this->user);
                $this->fail('An invalid structure was accepted.');
            } catch (ValidationException) {
            }
        }
        $this->assertTrue(true);
    }

    public function test_an_employee_sees_only_their_own_published_payslips(): void
    {
        $mine = $this->employee(['user_id' => $this->staff()->id]);
        $other = $this->employee(['user_id' => $this->staff()->id]);
        foreach ([$mine, $other] as $e) {
            $this->salary($e);
        }
        $this->process();
        $payroll = app(PayrollService::class);
        $run = $payroll->calculate($payroll->createRun($this->user, $this->august()));
        $payroll->approve($run, $this->actor(['payroll.run.approve']));
        $slips = app(PayslipService::class);

        $this->assertCount(0, $slips->forEmployee($mine)); // approved but not published
        $payroll->publishPayslips($run->refresh());
        $this->assertCount(1, $slips->forEmployee($mine));
        $otherLine = $run->lines()->where('employee_id', $other->id)->first();
        $this->assertNull($slips->find($mine, $otherLine->id));
    }

    public function test_a_run_cannot_be_started_for_a_month_that_has_not_finished(): void
    {
        $this->expectException(ValidationException::class);
        $payroll = app(PayrollService::class);
        $payroll->createRun($this->user, $payroll->openPeriod($this->organization->id, $this->date('2026-09-10')));
    }
}
