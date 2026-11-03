<?php

namespace App\Services\Watch;

use App\Models\User;
use App\Models\Watch\SvCondition;
use App\Models\Watch\SvSignal;
use App\Services\AuditLogger;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Watches the diagnoses written at the hospital for the conditions it was told to watch. A case is a distinct patient with a diagnosis (confirmed or provisional, not ruled out) whose code begins with one of the condition's prefixes,
 * in a window of days ending on a Dhaka day. A signal is raised when the cases in the window reach the condition's minimum AND rise above the usual (the average of the earlier windows) by its spike factor - or, for a condition
 * the hospital has never had, when the minimum is reached - and, separately, when one district sends a cluster. One signal per condition, district and week; running again updates it, never repeats it. A signal is a prompt for a person to look,
 * not a finding: the officer investigates, confirms or dismisses it, and a confirmed one is reported to the health authority.
 */
class SurveillanceService
{
    public function __construct(private readonly WatchPolicy $policy, private readonly AuditLogger $audit) {}

    /** The window a condition counts in when it ends on this day. @return array{0:CarbonImmutable,1:CarbonImmutable} first day, last day (Dhaka days) */
    public function window(SvCondition $c, CarbonImmutable $on, int $back = 0): array
    {
        $last = Days::today($on)->subDays($back * $c->window_days);

        return [$last->subDays($c->window_days - 1), $last];
    }

    /**
     * Distinct patients with a matching diagnosis in the window, each with the district of the permanent address (null when none is recorded).
     *
     * @return Collection<int, WatchCase>
     */
    public function cases(SvCondition $c, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $rows = DB::table('diagnoses')->where('organization_id', $c->organization_id)->whereNull('deleted_at')->where('status', '!=', 'rule_out')->whereNotNull('code')
            ->whereBetween('recorded_at', [Days::bounds($from)[0], Days::bounds($to)[1]])
            ->where(function ($q) use ($c) {
                foreach ((array) $c->icd_prefixes as $prefix) {
                    $q->orWhereRaw('upper(code) like ?', [strtoupper(str_replace(['%', '_'], '', (string) $prefix)).'%']);
                }
            })->pluck('patient_id')->unique()->values();
        $district = DB::table('patient_addresses')->whereIn('patient_id', $rows)->where('address_type', 'permanent')->whereNull('deleted_at')->whereNotNull('district_id')->orderBy('is_primary')->orderBy('id')->pluck('district_id', 'patient_id');

        return $rows->map(fn ($id) => new WatchCase((int) $id, isset($district[$id]) ? (int) $district[$id] : null));
    }

    /** The usual number of cases per window: the average of the earlier windows. */
    public function baseline(SvCondition $c, CarbonImmutable $on): float
    {
        $n = max(1, $this->policy->int('baseline_windows', 8));
        $sum = 0;
        for ($i = 1; $i <= $n; $i++) {
            [$f, $t] = $this->window($c, $on, $i);
            $sum += $this->cases($c, $f, $t)->count();
        }

        return round($sum / $n, 1);
    }

    /**
     * Looks at every active condition as of a day and raises what needs a person's attention.
     *
     * @return array{raised:int,updated:int}
     */
    public function scan(int $organizationId, ?CarbonImmutable $on = null): array
    {
        $on ??= CarbonImmutable::now();
        $out = ['raised' => 0, 'updated' => 0];
        foreach (SvCondition::query()->where(['organization_id' => $organizationId, 'active' => true])->orderBy('id')->get() as $c) {
            [$from, $to] = $this->window($c, $on);
            $cases = $this->cases($c, $from, $to);
            $base = $this->baseline($c, $on);
            $total = $cases->count();
            $everBefore = $base > 0;
            if ($total >= $c->min_cases && (! $everBefore || $total >= $base * (float) $c->spike_factor)) {
                $this->raise($c, null, $total, $base, $everBefore ? 'spike' : 'first', $from, $to, $on, $out);
            }
            foreach ($cases->whereNotNull('district_id')->groupBy('district_id') as $districtId => $group) {
                if ($group->count() >= $c->cluster_cases) {
                    $this->raise($c, (int) $districtId, $group->count(), null, 'cluster', $from, $to, $on, $out);
                }
            }
        }

        return $out;
    }

    /** @param  array{raised:int,updated:int}  $out */
    private function raise(SvCondition $c, ?int $districtId, int $cases, ?float $base, string $reason, CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $on, array &$out): void
    {
        $week = Days::today($on)->startOfWeek()->toDateString();
        $existing = SvSignal::query()->where(['organization_id' => $c->organization_id, 'condition_id' => $c->id, 'week_start' => $week])->where('district_id', $districtId)->first();
        if ($existing) {
            if ($existing->status === 'new' && $cases > $existing->cases) {
                $existing->update(['cases' => $cases, 'baseline' => $base, 'window_from' => $from->toDateString(), 'window_to' => $to->toDateString()]);
                $out['updated']++;
            }

            return;
        }
        $s = SvSignal::create(['organization_id' => $c->organization_id, 'condition_id' => $c->id, 'district_id' => $districtId, 'week_start' => $week, 'window_from' => $from->toDateString(), 'window_to' => $to->toDateString(),
            'cases' => $cases, 'baseline' => $base, 'reason' => $reason, 'status' => 'new', 'raised_at' => $on]);
        $out['raised']++;
        $this->audit->log('CREATE', SvSignal::class, $s->id, null, ['condition' => $c->code, 'cases' => $cases, 'reason' => $reason]);
        $this->alert($c, $s, $districtId);
    }

    private function alert(SvCondition $c, SvSignal $s, ?int $districtId): void
    {
        try {
            $place = $districtId ? ' ('.DB::table('geo_districts')->where('id', $districtId)->value('name_en').')' : '';
            $who = User::query()->permission('watch.signal.view')->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $c->organization_id))->limit(30)->pluck('id')->map(fn ($i) => (int) $i)->all();
            app(AlertService::class)->raise(new AlertRequest(organizationId: (int) $c->organization_id, category: 'disease_watch', severity: $c->severity, sourceModule: 'watch', sourceType: SvSignal::class, sourceId: $s->id, recipientUserIds: $who,
                message: "{$c->name}{$place}: {$s->cases} case(s) in {$c->window_days} days".($s->baseline !== null ? ", usually {$s->baseline}." : '.'), ackRequired: false, dedupKey: 'watch:'.$s->id));
        } catch (Throwable) {
            // an alert must never undo the scan
        }
    }

    public function investigate(SvSignal $s, User $by): SvSignal
    {
        if ($s->status !== 'new') {
            throw ValidationException::withMessages(['status' => __('Only a new signal can be taken up.')]);
        }
        $s->update(['status' => 'investigating', 'decided_by' => $by->id]);
        $this->audit->log('UPDATE', SvSignal::class, $s->id, ['status' => 'new'], ['status' => 'investigating']);

        return $s;
    }

    /** The officer's decision: confirmed (a real rise or cluster) or dismissed (a count that is not a concern), with the reason. */
    public function decide(SvSignal $s, bool $confirm, string $note, User $by): SvSignal
    {
        if (! in_array($s->status, ['new', 'investigating'], true)) {
            throw ValidationException::withMessages(['status' => __('This signal has already been decided.')]);
        }
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['decision_note' => __('Say, in a few words, what you found.')]);
        }
        $before = $s->status;
        $s->update(['status' => $confirm ? 'confirmed' : 'dismissed', 'decision_note' => mb_substr(trim($note), 0, 500), 'decided_by' => $by->id, 'decided_at' => now()]);
        $this->audit->log('UPDATE', SvSignal::class, $s->id, ['status' => $before], ['status' => $s->status]);

        return $s;
    }

    /** A confirmed signal, told to the health authority. */
    public function report(SvSignal $s, string $to, ?string $reference, User $by): SvSignal
    {
        if ($s->status !== 'confirmed' || $s->reported_at !== null) {
            throw ValidationException::withMessages(['status' => __('Only a confirmed signal that has not been reported can be reported.')]);
        }
        if (trim($to) === '') {
            throw ValidationException::withMessages(['reported_to' => __('Say who it was reported to.')]);
        }
        $s->update(['reported_at' => now(), 'reported_to' => mb_substr(trim($to), 0, 120), 'report_reference' => $reference !== null && trim($reference) !== '' ? mb_substr(trim($reference), 0, 60) : null, 'reported_by' => $by->id]);
        $this->audit->log('UPDATE', SvSignal::class, $s->id, null, ['reported_to' => $s->reported_to, 'reference' => $s->report_reference]);

        return $s;
    }

    /** Condition numbers are the hospital's to set; the authority decides what it must report. */
    public function updateCondition(SvCondition $c, array $d): SvCondition
    {
        $v = [
            'window_days' => (int) ($d['window_days'] ?? $c->window_days), 'min_cases' => (int) ($d['min_cases'] ?? $c->min_cases), 'spike_factor' => (float) ($d['spike_factor'] ?? $c->spike_factor),
            'cluster_cases' => (int) ($d['cluster_cases'] ?? $c->cluster_cases), 'severity' => (string) ($d['severity'] ?? $c->severity), 'active' => (bool) ($d['active'] ?? $c->active),
        ];
        if ($v['window_days'] < 3 || $v['window_days'] > 60 || $v['min_cases'] < 1 || $v['min_cases'] > 500 || $v['spike_factor'] < 1 || $v['spike_factor'] > 20 || $v['cluster_cases'] < 1 || ! in_array($v['severity'], ['warning', 'critical'], true)) {
            throw ValidationException::withMessages(['window_days' => __('Check the numbers: a window of 3 to 60 days, at least 1 case, a factor from 1 to 20.')]);
        }
        $c->update($v);

        return $c;
    }
}
