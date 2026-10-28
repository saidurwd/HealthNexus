<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Department;
use App\Models\HospitalHoliday;
use App\Models\Hr\HrAppraisal;
use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrDevice;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrJob;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrLeaveType;
use App\Models\Hr\HrOvertimeEntry;
use App\Models\Hr\HrPayrollPeriod;
use App\Models\Hr\HrPayrollRun;
use App\Models\Hr\HrProviderLink;
use App\Models\Hr\HrPunch;
use App\Models\Hr\HrRegularization;
use App\Models\Hr\HrRosterEntry;
use App\Models\Hr\HrShift;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\ChecklistService;
use App\Services\Hr\CompensationService;
use App\Services\Hr\ContractService;
use App\Services\Hr\CredentialService;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\JobService;
use App\Services\Hr\LeaveBalanceService;
use App\Services\Hr\LeaveRequestService;
use App\Services\Hr\OvertimeService;
use App\Services\Hr\PaymentFileService;
use App\Services\Hr\PayrollJournalService;
use App\Services\Hr\PayrollService;
use App\Services\Hr\PerformanceService;
use App\Services\Hr\ProviderLinkService;
use App\Services\Hr\PunchIngestService;
use App\Services\Hr\RecruitmentService;
use App\Services\Hr\RosterService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * People and payroll, six months. The register is built from the demo logins (every job role gets an employee) plus a ward's worth of other staff, with jobs,
 * contracts, licences, bank details and salary structures. Every day: the roster is made, people punch in and out (some forget, some are late, one device is offline
 * for a day and sends its backlog later, one code is unknown until HR maps it), attendance is worked out, leave is asked for and decided by managers and HR, corrections
 * are asked for and decided. Each month ends in a payroll run: overtime approved, a second person approves, payslips published, bank and wallet files prepared, the
 * journal built. One month has a festival bonus and a later supplementary run for arrears. Hires come through a vacancy, three people leave through offboarding.
 */
class HrHistory implements DemoHistoryModule
{
    private const FIRST = ['Rahim', 'Karim', 'Sumon', 'Jahid', 'Faisal', 'Tanvir', 'Mizanur', 'Habib', 'Arif', 'Nazmul', 'Rubel', 'Imran', 'Shahin', 'Mamun', 'Kamal', 'Sohel', 'Anwar', 'Delwar', 'Farhan', 'Hasan',
        'Rina', 'Shirin', 'Nasrin', 'Farzana', 'Sultana', 'Mitu', 'Rokeya', 'Salma', 'Taslima', 'Jesmin', 'Parvin', 'Laila', 'Mahfuza', 'Ruma', 'Shapla', 'Moushumi', 'Nusrat', 'Sabina', 'Tania', 'Yasmin'];

    private const LAST = ['Ahmed', 'Hossain', 'Islam', 'Rahman', 'Khan', 'Chowdhury', 'Akter', 'Begum', 'Sarker', 'Mia', 'Uddin', 'Sheikh', 'Talukder', 'Bhuiyan', 'Mondal', 'Das', 'Roy', 'Paul', 'Biswas', 'Karmakar'];

    /** code => [title, family, grade, licences, basic range, clinical allowance] */
    private const JOBS = [
        'MO' => ['Medical officer', 'clinical', 'G3', ['bmdc'], [90000, 130000], 12000],
        'CONS' => ['Consultant', 'clinical', 'G1', ['bmdc'], [140000, 220000], 20000],
        'NURSE' => ['Staff nurse', 'nursing', 'G7', ['bnmc'], [26000, 38000], 5000],
        'LAB' => ['Laboratory technologist', 'allied', 'G6', [], [30000, 42000], 3000],
        'PHARM' => ['Pharmacist', 'allied', 'G5', ['pharmacy_council'], [36000, 52000], 3000],
        'ADMIN' => ['Administrative officer', 'admin', 'G6', [], [24000, 40000], 0],
        'ACCT' => ['Accountant', 'admin', 'G5', [], [38000, 60000], 0],
        'SUPPORT' => ['Housekeeping and support', 'support', 'G9', [], [13000, 18000], 0],
        'HEAD' => ['Department head', 'admin', 'G4', [], [60000, 90000], 5000],
    ];

    /** role-name fragment => job code */
    private const ROLE_JOB = ['doctor' => 'MO', 'consultant' => 'CONS', 'surgeon' => 'CONS', 'anesth' => 'CONS', 'radiolog' => 'CONS', 'nurs' => 'NURSE', 'pharm' => 'PHARM', 'lab' => 'LAB', 'cashier' => 'ACCT', 'billing' => 'ACCT', 'account' => 'ACCT', 'ward' => 'SUPPORT', 'house' => 'SUPPORT'];

    /** @var array<string, int> */
    private array $summary = [];

    public function __construct(
        private readonly EmployeeService $employees,
        private readonly JobService $jobs,
        private readonly ContractService $contracts,
        private readonly CredentialService $credentials,
        private readonly ChecklistService $checklists,
        private readonly RecruitmentService $recruitment,
        private readonly RosterService $roster,
        private readonly PunchIngestService $punches,
        private readonly AttendanceService $attendance,
        private readonly LeaveBalanceService $balances,
        private readonly LeaveRequestService $leave,
        private readonly OvertimeService $overtime,
        private readonly CompensationService $compensation,
        private readonly PayrollService $payroll,
        private readonly PaymentFileService $files,
        private readonly PayrollJournalService $journals,
        private readonly PerformanceService $performance,
        private readonly ProviderLinkService $links,
    ) {}

    public function name(): string
    {
        return 'People and payroll';
    }

    public function module(): string
    {
        return 'hr';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $this->summary = array_fill_keys(['employees', 'hires', 'leavers', 'roster', 'punches', 'leave', 'leave_rejected', 'corrections', 'payroll', 'supplementary', 'skipped', 'ot'], 0);
        $hrOfficer = $context->staff('hr_officer')->first();
        $hrManager = $context->staff('hr_manager')->first();
        $payrollOfficer = $context->staff('payroll_officer')->first();
        $payrollManager = $context->staff('payroll_manager')->first();

        [$shifts, $jobs, $departments] = $context->at($context->from->subDay()->setTime(9, 0), fn () => $this->setup($context, $hrOfficer));
        $context->at($context->from->subDay()->setTime(11, 0), fn () => $this->people($context, $hrOfficer, $payrollManager, $jobs, $departments));
        $heads = HrEmployee::query()->where('organization_id', $org)->whereNotNull('user_id')->whereHas('job', fn ($q) => $q->where('code', 'HEAD'))->get()->keyBy('department_id');
        $device = HrDevice::where('organization_id', $org)->orderBy('id')->first();

        $days = collect($context->days());
        $offlineDay = (int) round($days->count() * 0.3);
        $unknownFrom = (int) round($days->count() * 0.5);
        $backlog = [];
        $dayNo = 0;
        $vacancyDay = (int) round($days->count() * 0.25);
        $leaverDays = [(int) round($days->count() * 0.4), (int) round($days->count() * 0.6), (int) round($days->count() * 0.75)];
        $firstMonth = $context->from->startOfMonth();

        $startedAt = microtime(true);
        foreach ($days as $day) {
            $dayNo++;
            getenv('DEMO_PROGRESS') && $dayNo % 5 === 0 && fwrite(STDERR, sprintf("hr day %d of %d at %.0fs\n", $dayNo, $days->count(), microtime(true) - $startedAt));

            // month start: yearly entitlement once, earned leave every month, licences swept
            if ($day->day === 1 || $dayNo === 1) {
                $context->at($day->setTime(6, 0), function () use ($org, $day, $dayNo) {
                    if ($dayNo === 1 || $day->month === 1) {
                        $this->balances->openYear($org, $day->year);
                    }
                    $this->balances->accrueMonth($org, $day);
                    $this->credentials->sweep($org);
                });
            }

            // the roster two days ahead
            $context->at($day->setTime(7, 0), fn () => $this->makeRoster($context, $day->addDays(2), $shifts, $hrOfficer));
            if ($dayNo === 1) {
                $context->at($day->setTime(7, 5), fn () => $this->makeRoster($context, $day, $shifts, $hrOfficer));
                $context->at($day->setTime(7, 6), fn () => $this->makeRoster($context, $day->addDay(), $shifts, $hrOfficer));
            }

            // vacancy, hires and leavers
            if ($dayNo === $vacancyDay) {
                $context->at($day->setTime(9, 0), fn () => $this->hire($context, $hrOfficer, $payrollManager, $jobs, $departments, $day));
            }
            if (in_array($dayNo, $leaverDays, true)) {
                $context->at($day->setTime(9, 30), fn () => $this->leaver($context, $hrManager, $hrOfficer, $day, $dayNo === $leaverDays[1]));
            }
            if ($dayNo === (int) round($days->count() * 0.55)) {
                $context->at($day->setTime(10, 0), fn () => $this->suspend($hrManager, $day));
            }

            // leave: requests in the morning, decisions the day after
            $context->at($day->setTime(10, 0), fn () => $this->askForLeave($context, $day));
            $context->at($day->setTime(14, 0), fn () => $this->decideLeave($context, $heads, $hrOfficer));

            // end of day: punches (one day the device is offline and sends them next morning), attendance, corrections
            if ($backlog && $dayNo === $offlineDay + 1) {
                $context->at($day->setTime(8, 30), function () use (&$backlog, $device, $org) {
                    $this->punches->ingest($org, $device, $backlog);
                    $this->summary['punches'] += count($backlog);
                    $backlog = [];
                });
            }
            $context->at($day->setTime(23, 30), function () use ($context, $day, $dayNo, $offlineDay, $unknownFrom, $device, $org, &$backlog) {
                $rows = $this->dayPunches($context, $day, $dayNo >= $unknownFrom && $dayNo < $unknownFrom + 3);
                if ($dayNo === $offlineDay) {
                    $backlog = $rows;
                } else {
                    $this->punches->ingest($org, $device, $rows, 'device', false);
                    $this->summary['punches'] += count($rows);
                }
                $this->attendance->processRange($org, $day, $day);
                HrPunch::query()->where('organization_id', $org)->whereNull('processed_at')->whereNotNull('employee_id')->update(['processed_at' => now()]);
            });
            if ($dayNo === $unknownFrom + 3) {
                $context->at($day->setTime(9, 0), fn () => $this->mapUnknown($org, $hrOfficer));
            }
            $context->at($day->setTime(12, 0), fn () => $this->corrections($context, $day, $heads, $hrOfficer));

            // month end: overtime found; the 5th: payroll for the month that ended
            if ($day->isSameDay($day->endOfMonth())) {
                $context->at($day->setTime(22, 0), fn () => $this->overtime->syncFromAttendance($org, $day->startOfMonth(), $day));
            }
            if ($day->day === 5) {
                $context->at($day->setTime(10, 0), fn () => $this->monthEnd($context, $day, $firstMonth, $payrollOfficer, $payrollManager, $heads, $hrOfficer));
            }
            if ($dayNo === (int) round($days->count() * 0.45)) {
                $context->at($day->setTime(11, 30), fn () => $this->performanceCycle($context, $day, $hrManager));
            }
        }

        $runs = HrPayrollRun::where('organization_id', $org)->whereIn('status', ['approved', 'paid'])->count();

        return sprintf(
            '%d employees (%d hired through a vacancy, %d left), %d duties rostered, %d punches, %d leave requests (%d refused), %d attendance corrections, %d overtime claims paid, %d payroll runs approved (%d correction run), %d steps skipped',
            HrEmployee::where('organization_id', $org)->count(), $this->summary['hires'], $this->summary['leavers'], $this->summary['roster'], $this->summary['punches'], $this->summary['leave'], $this->summary['leave_rejected'],
            $this->summary['corrections'], $this->summary['ot'], $runs, $this->summary['supplementary'], $this->summary['skipped'],
        );
    }

    /** @return array{0: Collection<string, HrShift>, 1: Collection<string, HrJob>, 2: Collection<int, Department>} */
    private function setup(HistoryContext $context, User $by): array
    {
        $org = $context->organization->id;
        foreach ([['SURG', 'Surgery'], ['MED', 'Medicine'], ['CAS', 'Casualty'], ['PATH', 'Pathology'], ['ADMIN', 'Administration']] as [$code, $name]) {
            if (Department::query()->where('organization_id', $org)->count() < 5) {
                Department::query()->firstOrCreate(['organization_id' => $org, 'branch_id' => $context->branch->id, 'code' => $code], ['name' => $name, 'is_active' => true]);
            }
        }
        $departments = Department::query()->where('organization_id', $org)->orderBy('id')->limit(6)->get();
        $this->balances->seedTypes($org);
        $this->compensation->seedComponents($org);

        $jobs = collect();
        foreach (self::JOBS as $code => [$title, $family, $grade, $licences]) {
            $jobs[$code] = HrJob::query()->where(['organization_id' => $org, 'code' => $code])->first() ?? $this->jobs->createJob($org, $code, $title, $family, $grade, $licences);
        }
        $shifts = collect([
            'MORNING' => $this->roster->createShift($org, 'MORNING', 'Morning', '07:00', '15:00', 30, 10),
            'EVENING' => $this->roster->createShift($org, 'EVENING', 'Evening', '15:00', '23:00', 30, 10),
            'NIGHT' => $this->roster->createShift($org, 'NIGHT', 'Night', '23:00', '07:00', 30, 10),
            'GENERAL' => $this->roster->createShift($org, 'GENERAL', 'General office', '09:00', '17:00', 60, 15),
        ]);
        foreach ($departments->take(3) as $department) {
            $this->roster->require($org, $department->id, $shifts['MORNING'], 3);
            $this->roster->require($org, $department->id, $shifts['NIGHT'], 2);
        }
        foreach ([['GATE', 'Main gate fingerprint', 'fingerprint'], ['WARD', 'Ward face reader', 'face']] as [$code, $name, $kind]) {
            HrDevice::create(['organization_id' => $org, 'branch_id' => $context->branch->id, 'code' => $code, 'name' => $name, 'kind' => $kind]);
        }
        // a few public holidays inside the history
        $span = $context->from->diffInDays($context->to);
        foreach ([0.18, 0.42, 0.7] as $i => $at) {
            $date = $context->from->addDays((int) round($span * $at));
            $date->dayOfWeek === 5 && $date = $date->addDay();
            HospitalHoliday::query()->firstOrCreate(['organization_id' => $org, 'date' => $date->toDateString(), 'name' => ['National holiday', 'Eid holiday', 'Victory Day'][$i]], ['is_recurring_annually' => false]);
        }

        return [$shifts, $jobs, $departments];
    }

    /** The register: one employee per demo job-role login, department heads with their own logins, and other staff. */
    private function people(HistoryContext $context, User $by, User $payrollManager, Collection $jobs, Collection $departments): void
    {
        $org = $context->organization->id;
        $start = $context->from->subYears(2);
        $this->nameIndex = 0;
        $make = function (string $jobCode, ?Department $department, ?User $login = null, ?string $joined = null, ?HrEmployee $head = null) use ($org, $by, $jobs, $context): HrEmployee {
            [$first, $last, $gender] = $this->nextName();
            $job = $jobs[$jobCode];
            $employee = $this->employees->create($by, $org, [
                'first_name' => $first, 'last_name' => $last, 'gender' => $gender, 'date_of_birth' => CarbonImmutable::now()->subYears(mt_rand(24, 56))->subDays(mt_rand(0, 360))->toDateString(), 'phone' => '01'.mt_rand(700000000, 999999999),
                'email' => strtolower($first.'.'.$last.mt_rand(1, 99)).'@staff.example.invalid', 'department_id' => $department?->id, 'branch_id' => $context->branch->id, 'job_id' => $job->id, 'manager_id' => $head?->id,
                'user_id' => $login?->id, 'joined_on' => $joined ?? $context->from->subDays(mt_rand(60, 900))->toDateString(), 'device_code' => (string) (1000 + HrEmployee::where('organization_id', $org)->count() + 1),
                'national_id' => (string) mt_rand(1000000000000, 9999999999999), 'tin' => (string) mt_rand(100000000000, 999999999999), 'weekly_off' => 'fri',
            ]);
            $employee->update(['status' => 'active', 'category' => $jobCode === 'CONS' && mt_rand(1, 3) === 1 ? 'visiting' : (mt_rand(1, 10) === 1 ? 'contract' : 'permanent')]);
            $roll = mt_rand(1, 100);
            $roll <= 85 ? $employee->update(['bank_name' => ['City Bank', 'Dutch-Bangla Bank', 'BRAC Bank', 'Islami Bank'][mt_rand(0, 3)], 'bank_account' => (string) mt_rand(1000000000, 9999999999)]) : ($roll <= 96 ? $employee->update(['mobile_wallet' => '018'.mt_rand(10000000, 99999999)]) : null);
            $this->contracts->start($employee, $employee->category === 'contract' ? 'fixed_term' : ($employee->category === 'visiting' ? 'visiting' : 'permanent'), $employee->joined_on->toDateString(), $employee->category === 'contract' ? $context->from->addMonths(mt_rand(2, 9))->toDateString() : ($employee->category === 'visiting' ? $context->to->addYear()->toDateString() : null), $by, 30, 0);
            $this->structure($employee, $jobCode, $employee->joined_on->toDateString(), $by);
            foreach ((array) $job->required_credentials as $type) {
                $this->credentials->add($employee, $type, strtoupper($type).'-'.mt_rand(10000, 99999), strtoupper($type).' council', $employee->joined_on->subYears(2)->toDateString(), $context->from->addDays(mt_rand(-20, 1400))->toDateString());
            }
            in_array($jobCode, ['MO', 'CONS', 'NURSE'], true) && $this->credentials->add($employee, mt_rand(0, 1) ? 'bls' : 'acls', 'RC-'.mt_rand(1000, 9999), 'Resuscitation Council', $context->from->subYear()->toDateString(), $context->from->addDays(mt_rand(30, 700))->toDateString());

            return $employee->refresh();
        };

        // heads: one per department, each with a login (the manager who approves their team's leave)
        $heads = collect();
        foreach ($departments->take(5) as $department) {
            $login = User::query()->firstOrCreate(['email' => 'head.'.strtolower($department->code).'@'.strtolower($context->organization->code).'.example.invalid'], ['name' => 'Head of '.$department->name, 'password' => bcrypt(bin2hex(random_bytes(8))), 'is_active' => true]);
            $login->organizations()->syncWithoutDetaching([$org => ['access_level' => 'staff', 'is_default' => true]]);
            $login->branches()->syncWithoutDetaching([$context->branch->id => ['access_level' => 'staff', 'organization_id' => $org, 'is_default' => true]]);
            $login->syncRoles(['department_manager']);
            $heads[$department->id] = $make('HEAD', $department, $login);
            $heads[$department->id]->update(['category' => 'permanent']);
        }

        // every demo job-role login is an employee, so each can open "My work"
        $roleUsers = User::query()->where('email', 'like', 'role.%@%')->whereHas('organizations', fn ($q) => $q->where('organizations.id', $org))->with('roles')->orderBy('id')->get();
        $officeRoles = 0;
        $roleEmployees = 0;
        foreach ($roleUsers as $i => $login) {
            if ($roleEmployees >= 26) {
                break;
            }
            $role = (string) ($login->roles->first()?->getAttribute('name') ?? '');
            $code = null;
            foreach (self::ROLE_JOB as $fragment => $job) {
                if (str_contains($role, $fragment)) {
                    $code = $job;
                    break;
                }
            }
            if ($code === null) {
                if (! in_array($role, ['receptionist', 'hospital_admin', 'hr_officer', 'hr_manager', 'payroll_officer', 'payroll_manager', 'storekeeper', 'inventory_manager'], true) || $officeRoles++ >= 4) {
                    continue; // the other demo logins are not employees of this hospital's register
                }
                $code = 'ADMIN';
            }
            $department = $departments[$i % $departments->count()];
            $make($code, $department, $login, null, $heads[$department->id] ?? null);
            $roleEmployees++;
        }
        // other staff: nurses, a few doctors, technicians, pharmacy, office and housekeeping
        foreach (['NURSE' => 8, 'MO' => 2, 'LAB' => 1, 'ADMIN' => 1, 'SUPPORT' => 3] as $code => $count) {
            for ($n = 0; $n < $count; $n++) {
                $department = $code === 'ADMIN' || $code === 'SUPPORT' ? $departments->last() : $departments[$n % $departments->count()];
                $make($code, $department, null, null, $heads[$department->id] ?? null);
            }
        }
        // a few loans and advances
        foreach (HrEmployee::where('organization_id', $org)->where('category', 'permanent')->whereNull('user_id')->orderBy('id')->limit(4)->get() as $i => $employee) {
            $loan = $this->compensation->requestLoan($employee, $i % 2 ? 'advance' : 'loan', $i % 2 ? '15000' : '60000', $i % 2 ? '5000' : '5000', $context->from->startOfMonth(), 'Family need');
            $this->compensation->approveLoan($loan, $payrollManager);
        }
        // provider links are proposed; most are confirmed, a couple are left for HR to look at
        $this->links->propose($org);
        HrProviderLink::query()->where('organization_id', $org)->where('status', 'pending')->orderByDesc('score')->get()->groupBy('provider_id')->values()->each(function ($group, $i) use ($by) {
            $link = $group->first();
            $i % 5 === 4 ? null : ($group->count() === 1 ? $this->links->accept($link, $by) : null);
        });
        unset($start);
        $this->summary['employees'] = HrEmployee::where('organization_id', $org)->count();
    }

    private function structure(HrEmployee $employee, string $jobCode, string $from, User $by): void
    {
        [, , , , [$min, $max], $allowance] = self::JOBS[$jobCode];
        $basic = (int) (round(mt_rand($min, $max) / 500) * 500);
        $lines = [['component' => 'BASIC', 'amount' => $basic], ['component' => 'HRENT', 'percent' => 50], ['component' => 'MEDICAL', 'amount' => $basic > 60000 ? 2500 : 1500], ['component' => 'CONV', 'amount' => 1000]];
        $allowance > 0 && $lines[] = ['component' => 'DUTY', 'amount' => $allowance];
        if ($employee->category === 'permanent') {
            array_push($lines, ['component' => 'PF', 'percent' => 10], ['component' => 'PFER', 'percent' => 10]);
        }
        $this->compensation->newStructure($employee, CarbonImmutable::parse($from), $lines, $by, 'Joining pay');
    }

    private function makeRoster(HistoryContext $context, CarbonImmutable $on, Collection $shifts, User $by): void
    {
        $org = $context->organization->id;
        $week = (int) $on->format('W');
        HrEmployee::query()->where('organization_id', $org)->where('status', 'active')->whereDate('joined_on', '<=', $on->toDateString())->with('job')->orderBy('id')->each(function (HrEmployee $employee) use ($on, $shifts, $by, $week) {
            if ($employee->category === 'visiting' || $on->dayOfWeek === 5 || mt_rand(1, 100) <= 7) {
                return; // weekly off, a day off, or a visiting consultant (not on the duty roster)
            }
            $family = $employee->job?->family;
            $shift = match (true) {
                $family === 'nursing' => ['MORNING', 'EVENING', 'NIGHT'][($week + $employee->id) % 3],
                in_array($family, ['clinical', 'allied'], true) => ($employee->id % 4 === 0 ? 'EVENING' : 'MORNING'),
                $family === 'support' => $employee->id % 2 ? 'MORNING' : 'EVENING',
                default => 'GENERAL',
            };
            try {
                $this->roster->assign($employee, $on, $shifts[$shift], $by);
                $this->summary['roster']++;
            } catch (ValidationException) {
                // a conflict (rest after a night, hours, days in a row): the system said no, so that day stays free
            }
        });
        $this->roster->publish($org, $on, $on);
    }

    /** @return list<array{code: string, at: string}> */
    private function dayPunches(HistoryContext $context, CarbonImmutable $day, bool $useUnknownCode): array
    {
        $rows = [];
        $entries = HrRosterEntry::query()->with(['shift', 'employee'])->where('organization_id', $context->organization->id)->whereDate('work_date', $day->toDateString())->get();
        $unknownBy = $useUnknownCode ? $entries->first()?->employee_id : null;
        foreach ($entries as $entry) {
            if (mt_rand(1, 100) <= 2) {
                continue; // absent
            }
            [$start, $end] = $this->roster->window($entry->shift, $day);
            $code = $entry->employee_id === $unknownBy ? '9001' : (string) $entry->employee->device_code;
            $late = mt_rand(1, 100) <= 7;
            $in = $start->addMinutes($late ? mt_rand(18, 70) : mt_rand(-12, 8));
            $rows[] = ['code' => $code, 'at' => $in->toDateTimeString()];
            if (mt_rand(1, 100) <= 2) {
                continue; // forgot to punch out
            }
            $out = $end->addMinutes(mt_rand(1, 100) <= 6 ? mt_rand(45, 200) : mt_rand(0, 18));
            $rows[] = ['code' => $code, 'at' => $out->toDateTimeString()];
            if (mt_rand(1, 100) <= 4) {
                $rows[] = ['code' => $code, 'at' => $in->addSeconds(40)->toDateTimeString()]; // a double touch of the finger
            }
        }

        return $rows;
    }

    private function mapUnknown(int $org, User $by): void
    {
        $unknown = HrPunch::query()->where('organization_id', $org)->whereNull('employee_id')->value('device_code');
        $employee = $unknown ? HrEmployee::where('organization_id', $org)->where('status', 'active')->orderBy('id')->skip(3)->first() : null;
        if ($unknown && $employee) {
            try {
                $this->punches->mapCode($org, $unknown, $employee);
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        }
    }

    private function askForLeave(HistoryContext $context, CarbonImmutable $day): void
    {
        $org = $context->organization->id;
        $types = HrLeaveType::query()->where('organization_id', $org)->get()->keyBy('code');
        HrEmployee::query()->where('organization_id', $org)->where('status', 'active')->whereNotNull('manager_id')->inRandomOrder()->limit(max(1, (int) round(HrEmployee::where('organization_id', $org)->count() * 0.035)))->get()->each(function (HrEmployee $employee) use ($day, $types) {
            $code = mt_rand(1, 100) <= 55 ? 'CL' : (mt_rand(1, 100) <= 65 ? 'SL' : (mt_rand(1, 100) <= 70 ? 'EL' : ($employee->gender === 'female' && mt_rand(1, 40) === 1 ? 'ML' : 'LWP')));
            $start = $day->addDays(mt_rand(1, 12));
            $length = match ($code) {
                'CL' => mt_rand(1, 2), 'SL' => mt_rand(1, 3), 'EL' => mt_rand(2, 6), 'ML' => 30, default => mt_rand(1, 2)
            };
            try {
                $this->leave->request($employee, $types[$code], $start, $start->addDays($length - 1), $code === 'CL' && mt_rand(1, 6) === 1 && $length === 1, ['CL' => 'Family matter', 'SL' => 'Fever', 'EL' => 'Visiting family', 'ML' => 'Maternity', 'LWP' => 'Personal'][$code], $code === 'SL' || $code === 'ML' ? 1 : null);
                $this->summary['leave']++;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        });
    }

    /** @param  Collection<int, HrEmployee>  $heads */
    private function decideLeave(HistoryContext $context, Collection $heads, User $hr): void
    {
        $org = $context->organization->id;
        HrLeaveRequest::query()->with(['employee', 'type'])->where('organization_id', $org)->whereIn('status', ['submitted', 'manager_approved'])->where('created_at', '<', now()->subHours(6))->orderBy('id')->each(function (HrLeaveRequest $request) use ($hr) {
            $approve = mt_rand(1, 100) > 12;
            try {
                if ($request->status === 'submitted') {
                    $manager = $request->employee->manager_id ? HrEmployee::find($request->employee->manager_id)?->user : null;
                    $this->leave->decideAsManager($request, $manager ?? $hr, $approve, $approve ? null : 'Not possible: short-staffed that week');
                } else {
                    $this->leave->decideAsHr($request, $hr, $approve);
                }
                $approve || $this->summary['leave_rejected']++;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        });
    }

    /** @param  Collection<int, HrEmployee>  $heads */
    private function corrections(HistoryContext $context, CarbonImmutable $day, Collection $heads, User $hr): void
    {
        $org = $context->organization->id;
        $yesterday = $day->subDay();
        HrAttendanceDay::query()->with('employee')->where('organization_id', $org)->whereDate('work_date', $yesterday->toDateString())->where('status', 'half_day')->where('locked', false)->limit(3)->get()->each(function ($record) use ($yesterday, $hr) {
            try {
                if (HrRegularization::where(['employee_id' => $record->employee_id])->whereDate('work_date', $yesterday->toDateString())->exists()) {
                    return;
                }
                $shiftEnd = $record->shift ? $this->roster->window($record->shift, $yesterday)[1] : $yesterday->setTime(17, 0);
                $reg = $this->attendance->requestRegularization($record->employee, $yesterday, null, $shiftEnd->addMinutes(mt_rand(0, 10)), 'Forgot to punch out');
                $manager = $record->employee->manager_id ? HrEmployee::find($record->employee->manager_id)?->user : null;
                $this->attendance->decideRegularization($reg, $manager ?? $hr, mt_rand(1, 100) > 10);
                $this->summary['corrections']++;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        });
    }

    private function hire(HistoryContext $context, User $hr, User $payrollManager, Collection $jobs, Collection $departments, CarbonImmutable $day): void
    {
        $org = $context->organization->id;
        $position = $this->jobs->createPosition($jobs['NURSE'], 'NURSE-ICU', 'Staff nurse, new ward', $departments->first()->id, 40);
        $vacancy = $this->recruitment->openVacancy($org, 'Staff nurses for the new ward', $position, 3, $day->addDays(30)->toDateString());
        $hired = 0;
        foreach (range(1, 6) as $i) {
            [$first, $last] = $this->nextName();
            $candidate = $this->recruitment->addCandidate($vacancy, "$first $last", '01'.mt_rand(700000000, 999999999));
            foreach (['screened', 'interview', 'offered'] as $stage) {
                $candidate = $this->recruitment->advance($candidate, $stage);
            }
            if ($hired < 3 && $i <= 4) {
                $employee = $this->recruitment->hire($candidate, $hr, ['joined_on' => $day->addDays(5 + $hired * 2)->toDateString(), 'branch_id' => $context->branch->id, 'device_code' => (string) (2000 + $hired), 'gender' => 'female', 'weekly_off' => 'fri', 'bank_name' => 'City Bank', 'bank_account' => (string) mt_rand(1000000000, 9999999999)]);
                $employee->update(['bank_name' => 'City Bank', 'bank_account' => (string) mt_rand(1000000000, 9999999999), 'manager_id' => HrEmployee::where('department_id', $departments->first()->id)->whereHas('job', fn ($q) => $q->where('code', 'HEAD'))->value('id')]);
                $this->credentials->add($employee, 'bnmc', 'BNMC-'.mt_rand(10000, 99999), 'BNMC', $day->subYears(2)->toDateString(), $day->addYears(3)->toDateString());
                $this->structure($employee, 'NURSE', $day->addDays(5 + $hired * 2)->toDateString(), $hr);
                foreach ($employee->checklists()->first()->items()->where('required', true)->get() as $item) {
                    $this->checklists->tick($item, $hr);
                }
                $this->employees->changeStatus($employee->refresh(), 'active', $hr, 'Onboarding finished');
                $hired++;
                $this->summary['hires']++;
            } else {
                $this->recruitment->advance($candidate, 'rejected', 'Another candidate was a closer fit');
            }
        }
        unset($payrollManager);
    }

    private function leaver(HistoryContext $context, User $hrManager, User $hr, CarbonImmutable $day, bool $terminate): void
    {
        $org = $context->organization->id;
        $employee = HrEmployee::query()->where('organization_id', $org)->where('status', 'active')->whereNull('user_id')->whereDate('joined_on', '<', $context->from->toDateString())->whereHas('job', fn ($q) => $q->whereIn('code', ['NURSE', 'SUPPORT', 'ADMIN']))->inRandomOrder()->first();
        if (! $employee) {
            return;
        }
        try {
            $last = $day->addDays($terminate ? 0 : 9);
            $this->employees->separate($employee, $terminate ? 'terminated' : 'resigned', $last, $terminate ? 'Repeated absence without notice' : 'Moving abroad', $hrManager);
            $list = $this->checklists->open($employee->refresh(), 'offboarding', $hr);
            foreach ($list->items->where('required', true) as $item) {
                $this->checklists->tick($item, $hr);
            }
            $this->summary['leavers']++;
        } catch (ValidationException) {
            $this->summary['skipped']++;
        }
    }

    private function suspend(User $hrManager, CarbonImmutable $day): void
    {
        $employee = HrEmployee::query()->where('status', 'active')->whereNull('user_id')->inRandomOrder()->first();
        if ($employee) {
            $this->employees->changeStatus($employee, 'suspended', $hrManager, 'Under inquiry', $day);
            $this->employees->changeStatus($employee->refresh(), 'active', $hrManager, 'Inquiry closed, back on duty', $day->addDays(10));
        }
    }

    /** @param  Collection<int, HrEmployee>  $heads */
    private function monthEnd(HistoryContext $context, CarbonImmutable $day, CarbonImmutable $firstMonth, User $officer, User $manager, Collection $heads, User $hr): void
    {
        $org = $context->organization->id;
        $month = $day->subMonthNoOverflow()->startOfMonth();
        if ($month->lt($firstMonth) || $month->addMonth()->startOfMonth()->gt(now()) || HrPayrollRun::where('organization_id', $org)->whereHas('period', fn ($q) => $q->whereDate('month_start', $month->toDateString()))->where('run_type', 'regular')->where('status', '!=', 'cancelled')->exists()) {
            return;
        }
        // days before the history began have no attendance records yet: work them out once, so the first month is complete
        if ($month->equalTo($firstMonth)) {
            $this->attendance->processRange($org, $month, $context->from->subDay());
        }
        // the approvers of overtime are the department heads' logins
        HrOvertimeEntry::query()->with('employee')->where('organization_id', $org)->where('status', 'pending')->whereDate('work_date', '>=', $month->toDateString())->whereDate('work_date', '<=', $month->endOfMonth()->toDateString())->orderBy('id')->each(function ($entry) use ($hr) {
            try {
                $head = $entry->employee->manager_id ? HrEmployee::find($entry->employee->manager_id)?->user : null;
                mt_rand(1, 100) > 10 ? $this->overtime->approve($entry, $head ?? $hr) : $this->overtime->reject($entry, $head ?? $hr);
                $this->summary['ot']++;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        });
        $period = $this->payroll->openPeriod($org, $month);
        // a festival bonus of one month's basic for permanent staff, in the third month of the history
        if ($month->equalTo($firstMonth->addMonths(2))) {
            foreach (HrEmployee::where('organization_id', $org)->where('category', 'permanent')->where('status', 'active')->get() as $e) {
                $basic = $this->compensation->structureOn($e, $month)?->lines->firstWhere('component.code', 'BASIC')?->amount;
                $basic && $this->payroll->addAdjustment($e, $period, 'earning', 'Festival bonus', number_format((float) $basic, 2, '.', ''), 'Eid festival bonus: one basic salary', $officer);
            }
        }
        try {
            $run = $this->payroll->calculate($this->payroll->createRun($officer, $period));
            $run = $this->payroll->approve($run, $manager);
            $this->payroll->publishPayslips($run);
            foreach (['bank', 'mobile_wallet'] as $method) {
                $this->files->prepare($run, $method, $officer);
            }
            $this->journals->post($run);
            $this->payroll->markPaid($run);
            $this->summary['payroll']++;
        } catch (ValidationException $e) {
            $this->summary['skipped']++;

            return;
        }
        // arrears paid in a correction run one month after the second month of the history
        if ($month->equalTo($firstMonth->addMonths(2))) {
            $earlier = HrPayrollPeriod::where('organization_id', $org)->whereDate('month_start', $firstMonth->addMonth()->toDateString())->first();
            if ($earlier) {
                foreach (HrEmployee::where('organization_id', $org)->where('category', 'permanent')->where('status', 'active')->orderBy('id')->limit(3)->get() as $e) {
                    $this->payroll->addAdjustment($e, $earlier, 'earning', 'Arrears: grade correction', '2500.00', 'Grade change backdated to the previous month', $officer);
                }
                try {
                    $supp = $this->payroll->calculate($this->payroll->createRun($officer, $earlier, 'supplementary', 'Arrears for a grade correction'));
                    $this->payroll->approve($supp, $manager);
                    $this->payroll->publishPayslips($supp->refresh());
                    $this->summary['supplementary']++;
                } catch (ValidationException) {
                    $this->summary['skipped']++;
                }
            }
        }
    }

    private function performanceCycle(HistoryContext $context, CarbonImmutable $day, User $hrManager): void
    {
        $org = $context->organization->id;
        $cycle = $this->performance->openCycle($org, 'Half-yearly appraisal', $context->from->toDateString(), $day->toDateString());
        $training = $this->performance->createTraining($org, 'Basic life support refresher', $day->subDays(20)->toDateString(), true, 24);
        foreach (HrEmployee::where('organization_id', $org)->where('status', 'active')->get() as $employee) {
            mt_rand(1, 100) <= 85 && $this->performance->record($training, $employee, mt_rand(1, 100) <= 92 ? 'passed' : 'absent');
        }
        HrAppraisal::query()->where('cycle_id', $cycle->id)->with('employee')->orderBy('id')->get()->each(function (HrAppraisal $appraisal, $i) {
            if ($i % 5 === 4) {
                return; // a few still waiting
            }
            $reviewer = $appraisal->reviewer_id ? HrEmployee::find($appraisal->reviewer_id) : null;
            if (! $reviewer || $reviewer->id === $appraisal->employee_id) {
                return;
            }
            try {
                $appraisal = $this->performance->submit($appraisal, $reviewer, mt_rand(3, 5), 'Reliable and good with patients', $i % 3 === 0 ? 'Documentation could be faster' : null);
                $i % 2 === 0 && $this->performance->acknowledge($appraisal, $appraisal->employee);
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        });
        unset($hrManager);
    }

    private int $nameIndex = 0;

    /** The next made-up name: first name, last name, gender. Combinations do not repeat for several hundred people. */
    private function nextName(): array
    {
        $i = $this->nameIndex++;
        $first = self::FIRST[($i * 3) % count(self::FIRST)];

        return [$first, self::LAST[($i * 7 + intdiv($i, count(self::FIRST))) % count(self::LAST)], array_search($first, self::FIRST, true) >= 20 ? 'female' : 'male'];
    }
}
