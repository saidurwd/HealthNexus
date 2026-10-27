<?php

namespace App\Services\Insurance;

use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsurancePreauth;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Numbers for the insurance desk: what payers owe and for how long (aging), where claims stand, how often and why they fail. Works without Finance. */
class InsuranceReportService
{
    private const OPEN = ['submitted', 'acknowledged', 'queried', 'accepted', 'appealed', 'partially_paid'];

    /** @return Collection<int, array<string, mixed>> Receivable by payer, in buckets of days since submission. */
    public function aging(int $organizationId, ?CarbonInterface $asOf = null): Collection
    {
        $asOf ??= now();
        $edges = config('insurance.aging_buckets');
        $labels = [];
        foreach ($edges as $i => $from) {
            $labels[] = isset($edges[$i + 1]) ? ($from === 0 ? '0–'.$edges[$i + 1] : ($from + 1).'–'.$edges[$i + 1]) : ($from + 1).'+';
        }
        $rows = [];
        InsuranceClaim::query()->with('payer:id,name')->where('organization_id', $organizationId)->whereIn('status', self::OPEN)->whereNotNull('submitted_at')->orderBy('id')->each(function (InsuranceClaim $c) use (&$rows, $asOf, $edges, $labels) {
            $owed = $c->outstanding();
            if (bccomp($owed, '0', 2) <= 0) {
                return;
            }
            $days = (int) $c->submitted_at->diffInDays($asOf, true);
            $bucket = $labels[0];
            foreach ($edges as $i => $from) {
                if ($days > $from || ($i === 0 && $days >= 0)) {
                    $bucket = $labels[$i];
                }
            }
            $key = $c->payer_id;
            $rows[$key] ??= ['payer' => $c->payer->name, 'buckets' => array_fill_keys($labels, '0.00'), 'total' => '0.00', 'claims' => 0];
            $rows[$key]['buckets'][$bucket] = bcadd($rows[$key]['buckets'][$bucket], $owed, 2);
            $rows[$key]['total'] = bcadd($rows[$key]['total'], $owed, 2);
            $rows[$key]['claims']++;
        });

        /** @var list<array<string, mixed>> $list */
        $list = array_values($rows);

        return collect($list)->sortByDesc(fn ($r) => (float) $r['total'])->values();
    }

    /** @return array<string, mixed> */
    public function dashboard(int $organizationId): array
    {
        $byStatus = DB::table('insurance_claims')->where('organization_id', $organizationId)->selectRaw('status, count(*) c, sum(payer_share) amount')->groupBy('status')->get()->keyBy('status');
        $submittedThisMonth = InsuranceClaim::query()->where('organization_id', $organizationId)->where('submitted_at', '>=', now()->startOfMonth());
        $decided = InsuranceClaim::query()->where('organization_id', $organizationId)->whereNotNull('submitted_at')->whereIn('status', ['denied', 'rejected', 'paid', 'partially_paid', 'written_off', 'closed'])->count();
        $failed = InsuranceClaim::query()->where('organization_id', $organizationId)->whereIn('status', ['denied', 'rejected', 'written_off'])->count();
        $paidClaims = InsuranceClaim::query()->where('organization_id', $organizationId)->where('status', 'paid')->whereNotNull('submitted_at')->get(['submitted_at', 'updated_at']);

        return [
            'by_status' => $byStatus->map(fn ($r) => ['count' => (int) $r->c, 'amount' => number_format((float) $r->amount, 2, '.', '')])->all(),
            'submitted_this_month' => ['count' => (clone $submittedThisMonth)->count(), 'amount' => number_format((float) (clone $submittedThisMonth)->sum('payer_share'), 2, '.', '')],
            'outstanding' => number_format((float) InsuranceClaim::query()->where('organization_id', $organizationId)->whereIn('status', self::OPEN)->get()->sum(fn ($c) => (float) $c->outstanding()), 2, '.', ''),
            'denial_rate_percent' => $decided > 0 ? round(100 * $failed / $decided, 1) : 0.0,
            'avg_days_to_payment' => $paidClaims->isEmpty() ? null : round($paidClaims->avg(fn ($c) => $c->submitted_at->diffInDays($c->updated_at, true)), 1),
            'pending_preauths' => InsurancePreauth::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'expiring_preauths' => InsurancePreauth::query()->where('organization_id', $organizationId)->whereIn('status', ['approved', 'partial'])->whereBetween('valid_to', [now()->toDateString(), now()->addDays(7)->toDateString()])->count(),
            'deadlines_soon' => InsuranceClaim::query()->where('organization_id', $organizationId)->whereIn('status', ['draft', 'ready'])->whereBetween('filing_deadline', [now()->toDateString(), now()->addDays(14)->toDateString()])->count(),
            'open_queries' => DB::table('insurance_claim_queries')->where('organization_id', $organizationId)->where('kind', 'query')->where('status', 'open')->count(),
            'pending_writeoffs' => DB::table('insurance_writeoffs')->where('organization_id', $organizationId)->where('status', 'requested')->count(),
        ];
    }
}
