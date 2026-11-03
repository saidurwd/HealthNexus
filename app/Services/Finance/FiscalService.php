<?php

namespace App\Services\Finance;

use App\Models\Finance\FiscalYear;
use App\Models\Finance\Period;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Fiscal years, their monthly periods and the period lock. */
class FiscalService
{
    public function __construct(private readonly AuditLogger $audit, private readonly FinancePolicy $policy) {}

    /** A year of twelve monthly periods starting in the given month of the given calendar year. */
    public function createYear(int $organizationId, int $startYear, ?int $firstMonth = null, ?string $name = null): FiscalYear
    {
        $first = $firstMonth ?? max(1, min(12, $this->policy->int('period.first_month', 1)));
        $start = CarbonImmutable::create($startYear, $first, 1)->startOfDay();
        $end = $start->addMonths(12)->subDay();
        $name ??= $first === 1 ? (string) $startYear : $start->format('Y').'-'.$end->format('y');
        if (FiscalYear::query()->where('organization_id', $organizationId)->where('starts_on', '<=', $end)->where('ends_on', '>=', $start)->exists()) {
            throw ValidationException::withMessages(['year' => 'A fiscal year already covers part of these dates.']);
        }

        return DB::transaction(function () use ($organizationId, $start, $end, $name) {
            $year = FiscalYear::create(['organization_id' => $organizationId, 'name' => $name, 'starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString()]);
            for ($n = 1; $n <= 12; $n++) {
                $from = $start->addMonths($n - 1);
                Period::create(['organization_id' => $organizationId, 'fiscal_year_id' => $year->id, 'number' => $n, 'name' => $from->format('M Y'), 'starts_on' => $from->toDateString(), 'ends_on' => $from->endOfMonth()->toDateString()]);
            }
            $this->audit->log('FIN_YEAR_CREATED', FiscalYear::class, $year->id, null, ['name' => $name]);

            return $year;
        });
    }

    public function periodFor(int $organizationId, CarbonInterface|string $date): ?Period
    {
        $d = CarbonImmutable::parse($date)->toDateString();

        return Period::query()->where('organization_id', $organizationId)->whereDate('starts_on', '<=', $d)->whereDate('ends_on', '>=', $d)->first();
    }

    /** The open period a posting on this date goes into, or a clear reason why there is none. */
    public function openPeriodFor(int $organizationId, CarbonInterface|string $date, bool $allowLocked = false): Period
    {
        $period = $this->periodFor($organizationId, $date);
        if ($period === null) {
            throw new FinanceException('There is no fiscal period for '.CarbonImmutable::parse($date)->toDateString().'. Create the fiscal year first.');
        }
        if ($period->status === 'open' || ($allowLocked && $period->status === 'locked')) {
            return $period;
        }

        throw new FinanceException("The period {$period->name} is {$period->status}; nothing can be posted into it.");
    }

    public function lock(Period $period, User $by, ?string $note = null): Period
    {
        if ($period->status !== 'open') {
            throw ValidationException::withMessages(['period' => 'Only an open period can be locked.']);
        }
        $period->update(['status' => 'locked', 'locked_by' => $by->id, 'locked_at' => now(), 'lock_note' => $note]);
        $this->audit->log('FIN_PERIOD_LOCKED', Period::class, $period->id, null, ['period' => $period->name]);

        return $period;
    }

    public function unlock(Period $period, User $by, string $reason): Period
    {
        if ($period->status !== 'locked') {
            throw ValidationException::withMessages(['period' => 'Only a locked period can be opened again (a closed year cannot).']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why the period is opened again.']);
        }
        $period->update(['status' => 'open', 'locked_by' => null, 'locked_at' => null, 'lock_note' => null]);
        $this->audit->log('FIN_PERIOD_UNLOCKED', Period::class, $period->id, null, ['period' => $period->name, 'reason' => $reason, 'by' => $by->id]);

        return $period;
    }
}
