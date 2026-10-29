<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AnalyticsDqIssue;
use App\Models\Analytics\AnalyticsDqRule;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Checks the warehouse (and its agreement with the operational tables) after each load. A failing rule opens a visible issue for the hospital it concerns; when the rule passes again the issue
 * is closed by itself. A person can also set an issue aside with a reason. The rules are ours (code); the hospital sees their names and results.
 */
class DataQualityService
{
    public function __construct(private readonly Warehouse $warehouse, private readonly LoadRunner $runner) {}

    /** @return array<string,array{name:string,description:string,severity:string}> */
    public function catalogue(): array
    {
        return [
            'billing_total_agrees' => ['name' => 'Billed amounts agree with Billing', 'description' => 'The total of billed invoices in the warehouse equals the total in Billing.', 'severity' => 'error'],
            'payments_agree' => ['name' => 'Payments agree with Billing', 'description' => 'The total of completed payments in the warehouse equals the total in Billing.', 'severity' => 'error'],
            'visit_counts_agree' => ['name' => 'Visit counts agree with the records', 'description' => 'The number of visits in the warehouse equals the number of encounters.', 'severity' => 'warning'],
            'visit_without_department' => ['name' => 'Visits without a department', 'description' => 'A visit with no department cannot be counted in department figures.', 'severity' => 'warning'],
            'fact_without_patient' => ['name' => 'Records that could not be tied to a patient', 'description' => 'A visit, appointment or order whose patient is missing from the patient dimension.', 'severity' => 'error'],
            'discharge_before_admission' => ['name' => 'Discharge before admission', 'description' => 'An admission discharged before it started.', 'severity' => 'error'],
            'admission_open_too_long' => ['name' => 'Admissions open for over 60 days', 'description' => 'A patient admitted more than 60 days ago and not discharged: perhaps forgotten.', 'severity' => 'warning'],
            'lab_report_without_time' => ['name' => 'Reported lab orders without a turn-around time', 'description' => 'A report exists but its time could not be worked out (missing order or report time).', 'severity' => 'warning'],
            'date_not_in_calendar' => ['name' => 'Dates outside the calendar', 'description' => 'A fact dated outside the calendar dimension.', 'severity' => 'error'],
            'duplicate_current_patient' => ['name' => 'Patient with two current descriptions', 'description' => 'The patient dimension has two current rows for one person.', 'severity' => 'error'],
            'provider_unknown' => ['name' => 'Visits for a doctor not in the doctor list', 'description' => 'A visit whose doctor is missing from the provider dimension.', 'severity' => 'warning'],
            'ledger_balances' => ['name' => 'Books balance month by month', 'description' => 'In the monthly finance figures, debits equal credits.', 'severity' => 'error'],
            'loads_current' => ['name' => 'Warehouse loads are current', 'description' => 'Every load succeeded recently.', 'severity' => 'error'],
        ];
    }

    public function installRules(): int
    {
        $n = 0;
        foreach ($this->catalogue() as $code => $r) {
            $rule = AnalyticsDqRule::query()->firstOrCreate(['code' => $code], ['name' => $r['name'], 'description' => $r['description'], 'severity' => $r['severity'], 'active' => true]);
            $rule->wasRecentlyCreated && $n++;
        }

        return $n;
    }

    /** Run every active rule. @return array{rules:int,failing:int,opened:int,closed:int} */
    public function run(): array
    {
        $this->installRules();
        $rules = AnalyticsDqRule::query()->where('active', true)->get();
        $failing = $opened = $closed = 0;
        foreach ($rules as $rule) {
            $found = $this->check($rule->code);
            $stillBad = [];
            foreach ($found as $f) {
                $failing++;
                $stillBad[] = $f['organization_id'];
                $issue = AnalyticsDqIssue::query()->where(['rule_code' => $rule->code, 'status' => 'open'])->where('organization_id', $f['organization_id'])->first();
                if ($issue) {
                    $issue->update(['failing' => $f['failing'], 'detail' => mb_substr($f['detail'], 0, 300), 'sample' => $f['sample'], 'last_seen_at' => now()]);
                } else {
                    AnalyticsDqIssue::create(['organization_id' => $f['organization_id'], 'rule_code' => $rule->code, 'severity' => $rule->severity, 'detail' => mb_substr($f['detail'], 0, 300), 'sample' => $f['sample'], 'failing' => $f['failing'], 'status' => 'open', 'first_seen_at' => now(), 'last_seen_at' => now()]);
                    $opened++;
                }
            }
            // a rule that passes again closes its issues
            foreach (AnalyticsDqIssue::query()->where(['rule_code' => $rule->code, 'status' => 'open'])->get() as $issue) {
                if (! in_array($issue->organization_id, $stillBad, true)) {
                    $issue->update(['status' => 'resolved', 'resolved_at' => now(), 'note' => 'Passed on the next check.']);
                    $closed++;
                }
            }
        }

        return ['rules' => $rules->count(), 'failing' => $failing, 'opened' => $opened, 'closed' => $closed];
    }

    public function setAside(AnalyticsDqIssue $issue, User $by, string $note): void
    {
        $issue->update(['status' => 'ignored', 'resolved_by' => $by->id, 'resolved_at' => now(), 'note' => mb_substr($note, 0, 300)]);
    }

    /** @return list<array{organization_id:?int,failing:int,detail:string,sample:array<int,mixed>}> */
    private function check(string $code): array
    {
        $w = $this->warehouse;
        $perOrg = function (iterable $rows, string $detail) {
            $out = [];
            foreach ($rows as $r) {
                if ($r['failing'] > 0) {
                    $out[] = ['organization_id' => $r['org'], 'failing' => (int) $r['failing'], 'detail' => sprintf($detail, $r['failing']).(isset($r['extra']) ? ' '.$r['extra'] : ''), 'sample' => $r['sample'] ?? []];
                }
            }

            return $out;
        };

        return match ($code) {
            'billing_total_agrees' => $perOrg($this->orgs()->map(function ($org) use ($w) {
                $billing = (string) DB::table('billing_invoices')->where('organization_id', $org)->whereNotNull('finalized_at')->whereNotIn('status', ['draft', 'cancelled'])->sum('grand_total');
                $fact = (string) $w->table('fact_invoices')->where('organization_id', $org)->where('is_billed', true)->sum('grand_total');
                $diff = bcsub(bcadd($billing, '0', 2), bcadd($fact, '0', 2), 2);

                return ['org' => $org, 'failing' => bccomp($diff, '0', 2) === 0 ? 0 : 1, 'extra' => "Billing $billing, warehouse $fact.", 'sample' => ['billing' => $billing, 'warehouse' => $fact]];
            }), 'The billed total is off (%d difference).'),
            'payments_agree' => $perOrg($this->orgs()->map(function ($org) use ($w) {
                $billing = (string) DB::table('billing_payments')->where('organization_id', $org)->where('status', 'completed')->whereNotNull('invoice_id')->sum('amount');
                $fact = (string) $w->table('fact_payments')->where('organization_id', $org)->where('status', 'completed')->sum('amount');
                $billingAll = (string) DB::table('billing_payments')->where('organization_id', $org)->where('status', 'completed')->sum('amount');

                return ['org' => $org, 'failing' => bccomp(bcadd($billingAll, '0', 2), bcadd($fact, '0', 2), 2) === 0 ? 0 : 1, 'extra' => "Billing $billingAll, warehouse $fact.", 'sample' => ['billing' => $billingAll, 'warehouse' => $fact]];
            }), 'The payments total is off (%d difference).'),
            'visit_counts_agree' => $perOrg($this->orgs()->map(function ($org) use ($w) {
                $src = DB::table('encounters')->where('organization_id', $org)->count();
                $fact = $w->table('fact_encounters')->where('organization_id', $org)->count();
                $gap = abs($src - $fact);

                return ['org' => $org, 'failing' => $src > 0 && $gap / $src > 0.01 ? $gap : 0, 'extra' => "Records $src, warehouse $fact.", 'sample' => ['records' => $src, 'warehouse' => $fact]];
            }), '%d visits missing or extra.'),
            'visit_without_department' => $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => $w->table('fact_encounters')->where('organization_id', $org)->whereNull('department_id')->count()]), '%d visits have no department.'),
            'fact_without_patient' => $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => $w->table('fact_encounters')->where('organization_id', $org)->whereNull('patient_key')->count() + $w->table('fact_appointments')->where('organization_id', $org)->whereNull('patient_key')->count()
                + $w->table('fact_lab_orders')->where('organization_id', $org)->whereNull('patient_key')->count()]), '%d records have no patient in the warehouse.'),
            'discharge_before_admission' => $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => $w->table('fact_admissions')->where('organization_id', $org)->whereNotNull('discharge_date_key')->whereColumn('discharge_date_key', '<', 'date_key')->count(),
                'sample' => $w->table('fact_admissions')->where('organization_id', $org)->whereNotNull('discharge_date_key')->whereColumn('discharge_date_key', '<', 'date_key')->limit(5)->pluck('admission_id')->all()]), '%d admissions were discharged before they began.'),
            'admission_open_too_long' => $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => $w->table('fact_admissions')->where('organization_id', $org)->where('is_discharged', false)->where('date_key', '<', (int) now()->subDays(60)->format('Ymd'))->count()]), '%d patients have been in for over 60 days.'),
            'lab_report_without_time' => $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => $w->table('fact_lab_orders')->where('organization_id', $org)->where('reported', true)->whereNull('tat_report_min')->count()]), '%d reported lab orders have no turn-around time.'),
            'date_not_in_calendar' => $perOrg($this->orgs()->map(function ($org) use ($w) {
                $n = 0;
                foreach (['fact_encounters', 'fact_invoices', 'fact_appointments', 'fact_lab_orders'] as $t) {
                    $n += $w->table("$t as t")->leftJoin('dim_date as d', 'd.date_key', '=', 't.date_key')->where('t.organization_id', $org)->whereNull('d.date_key')->count();
                }

                return ['org' => $org, 'failing' => $n];
            }), '%d records are dated outside the calendar.'),
            'duplicate_current_patient' => (function () use ($w) {
                $n = (int) $w->connection()->selectOne('select count(*) as n from (select pseudonym from dim_patient where is_current = 1 group by pseudonym having count(*) > 1) t')->n;

                return $n > 0 ? [['organization_id' => null, 'failing' => $n, 'detail' => "$n patients have two current descriptions.", 'sample' => []]] : [];
            })(),
            'provider_unknown' => $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => $w->table('fact_encounters as e')->leftJoin('dim_provider as p', 'p.provider_id', '=', 'e.provider_id')->where('e.organization_id', $org)->whereNotNull('e.provider_id')->whereNull('p.provider_id')->count()]), '%d visits name a doctor that is not in the doctor list.'),
            'ledger_balances' => Schema::hasTable('fin_periods') ? $perOrg($this->orgs()->map(fn ($org) => ['org' => $org, 'failing' => (int) $w->connection()->selectOne('select count(*) as n from (select period_ym from fact_ledger_monthly where organization_id = ? group by period_ym having abs(sum(debit) - sum(credit)) > 0.01) t', [$org])->n]), '%d months where debits and credits differ.') : [],
            'loads_current' => (function () {
                $bad = $this->runner->problems();

                return $bad->isEmpty() ? [] : [['organization_id' => null, 'failing' => $bad->count(), 'detail' => $bad->count().' load(s) failed or are out of date: '.$bad->pluck('key')->implode(', ').'.', 'sample' => $bad->pluck('key')->all()]];
            })(),
            default => [],
        };
    }

    /** Hospitals that have anything in the warehouse. @return \Illuminate\Support\Collection<int,int> */
    private function orgs(): Collection
    {
        return Organization::query()->where('is_active', true)->pluck('id')->map(fn ($i) => (int) $i);
    }

    public function openCount(?int $organizationId = null): int
    {
        return AnalyticsDqIssue::query()->where('status', 'open')->when($organizationId, fn ($q) => $q->where(fn ($w) => $w->where('organization_id', $organizationId)->orWhereNull('organization_id')))->count();
    }
}
