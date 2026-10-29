<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Counts per day for the smaller modules, kept in one narrow table (date, hospital, branch, metric, value): patients registered, staff attendance, blood donations and issues, online payments,
 * complaints, ratings and interoperability messages. Rebuilt for the last N days each run.
 */
class DailyMetricsFact extends Loader
{
    public function key(): string
    {
        return 'fact_daily_metrics';
    }

    public function label(): string
    {
        return 'Daily counts (people, blood bank, portal, connections)';
    }

    public function mode(): string
    {
        return 'snapshot';
    }

    protected function target(): string
    {
        return 'fact_daily_metrics';
    }

    protected function uniqueBy(): array
    {
        return ['date_key', 'organization_id', 'branch_id', 'metric'];
    }

    /** @param array<string,array<int,mixed>> $acc */
    private function add(array &$acc, ?string $day, int $org, ?int $branch, string $metric, float $value = 1): void
    {
        if ($day === null) {
            return;
        }
        $k = "$day|$org|".($branch ?? 0)."|$metric";
        $acc[$k] ??= [(int) str_replace('-', '', $day), $org, $branch ?? 0, $metric, 0.0];
        $acc[$k][4] += $value;
    }

    public function load(?string $watermark): array
    {
        $days = (int) config('analytics.load.daily_metrics_days', 400);
        $cut = CarbonImmutable::now()->subDays($days)->toDateTimeString();
        $acc = [];
        $read = 0;
        $each = function (string $table, array $cols, string $when, callable $use) use (&$read, $cut) {
            foreach (DB::table($table)->where($when, '>=', $cut)->get($cols) as $r) {
                $read++;
                $use($r);
            }
        };
        $each('patients', ['organization_id', 'registered_at'], 'registered_at', fn ($r) => $this->add($acc, $this->localDay($r->registered_at), $r->organization_id, null, 'patients_registered'));
        foreach (DB::table('hr_attendance_days')->where('work_date', '>=', substr($cut, 0, 10))->get(['organization_id', 'work_date', 'status', 'late_minutes']) as $r) {
            $read++;
            $d = $this->day($r->work_date);
            if (in_array($r->status, ['present', 'late', 'half_day'], true)) {
                $this->add($acc, $d, $r->organization_id, null, 'hr_attended');
                $r->late_minutes > 0 && $this->add($acc, $d, $r->organization_id, null, 'hr_late');
            } elseif ($r->status === 'absent') {
                $this->add($acc, $d, $r->organization_id, null, 'hr_absent');
            } elseif ($r->status === 'leave') {
                $this->add($acc, $d, $r->organization_id, null, 'hr_on_leave');
            }
        }
        $each('blood_donations', ['organization_id', 'branch_id', 'collected_at', 'status'], 'collected_at', fn ($r) => $r->status === 'collected' ? $this->add($acc, $this->localDay($r->collected_at), $r->organization_id, $r->branch_id, 'blood_donations') : null);
        $each('blood_issues', ['organization_id', 'issued_at', 'status'], 'issued_at', fn ($r) => $this->add($acc, $this->localDay($r->issued_at), $r->organization_id, null, 'blood_units_issued'));
        $each('portal_payments', ['organization_id', 'completed_at', 'status', 'amount'], 'completed_at', function ($r) use (&$acc) {
            if ($r->status === 'succeeded') {
                $this->add($acc, $this->localDay($r->completed_at), $r->organization_id, null, 'portal_payments');
                $this->add($acc, $this->localDay($r->completed_at), $r->organization_id, null, 'portal_payment_amount', (float) $r->amount);
            }
        });
        $each('crm_complaints', ['organization_id', 'branch_id', 'created_at'], 'created_at', fn ($r) => $this->add($acc, $this->localDay($r->created_at), $r->organization_id, $r->branch_id, 'complaints_opened'));
        $each('crm_feedback', ['organization_id', 'branch_id', 'created_at', 'rating'], 'created_at', function ($r) use (&$acc) {
            $this->add($acc, $this->localDay($r->created_at), $r->organization_id, $r->branch_id, 'ratings_count');
            $this->add($acc, $this->localDay($r->created_at), $r->organization_id, $r->branch_id, 'ratings_sum', (float) $r->rating);
        });
        $each('integration_messages', ['organization_id', 'created_at', 'direction', 'status'], 'created_at', function ($r) use (&$acc) {
            $this->add($acc, $this->localDay($r->created_at), $r->organization_id, null, $r->direction === 'out' ? 'messages_out' : 'messages_in');
            $r->status === 'dead' && $this->add($acc, $this->localDay($r->created_at), $r->organization_id, null, 'messages_dead');
        });
        $rows = [];
        $now = now()->toDateTimeString();
        foreach ($acc as [$dateKey, $org, $branch, $metric, $value]) {
            $rows[] = ['date_key' => $dateKey, 'organization_id' => $org, 'branch_id' => $branch, 'metric' => $metric, 'value' => round($value, 2), 'loaded_at' => $now];
        }
        $this->warehouse->table('fact_daily_metrics')->where('date_key', '>=', (int) CarbonImmutable::parse($cut)->format('Ymd'))->delete();
        $written = $this->warehouse->upsert('fact_daily_metrics', $rows, $this->uniqueBy());

        return ['read' => $read, 'written' => $written, 'watermark' => now()->toDateString()];
    }
}
