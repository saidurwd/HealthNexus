<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodUnit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Utilization and wastage over a period, per component: how many bags came in, were used, and were thrown away or expired (and why). */
class BloodUtilizationReport
{
    /** @return array<string, array{received: int, issued: int, transfused: int, discarded: int, expired: int, wastage_percent: float, discard_reasons: array<string, int>}> */
    public function summary(int $organizationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $out = [];
        $received = BloodUnit::query()->where('organization_id', $organizationId)->where('component', '!=', 'whole_blood')->whereBetween('collected_at', [$from, $to])->selectRaw('component, count(*) c')->groupBy('component')->pluck('c', 'component');
        $issued = DB::table('blood_issues as i')->join('blood_units as u', 'u.id', '=', 'i.unit_id')->where('i.organization_id', $organizationId)->whereBetween('i.issued_at', [$from, $to])->selectRaw('u.component, count(*) c')->groupBy('u.component')->pluck('c', 'component');
        $used = DB::table('blood_transfusions as t')->join('blood_issues as i', 'i.id', '=', 't.issue_id')->join('blood_units as u', 'u.id', '=', 'i.unit_id')->where('t.organization_id', $organizationId)->whereBetween('t.started_at', [$from, $to])->selectRaw('u.component, count(*) c')->groupBy('u.component')->pluck('c', 'component');
        $discards = DB::table('blood_discards')->where('organization_id', $organizationId)->whereBetween('discarded_at', [$from, $to])->selectRaw('component, reason, count(*) c')->groupBy('component', 'reason')->get();
        $expired = BloodUnit::query()->where('organization_id', $organizationId)->where('status', BloodUnit::EXPIRED)->whereBetween('expires_at', [$from, $to])->selectRaw('component, count(*) c')->groupBy('component')->pluck('c', 'component');

        foreach (array_keys(config('blood.components')) as $component) {
            $discarded = (int) $discards->where('component', $component)->sum('c');
            $exp = (int) ($expired[$component] ?? 0);
            $in = (int) ($received[$component] ?? 0);
            $out[$component] = [
                'received' => $in, 'issued' => (int) ($issued[$component] ?? 0), 'transfused' => (int) ($used[$component] ?? 0), 'discarded' => $discarded, 'expired' => $exp,
                'wastage_percent' => $in > 0 ? round(100 * ($discarded + $exp) / $in, 1) : 0.0,
                'discard_reasons' => $discards->where('component', $component)->pluck('c', 'reason')->map(fn ($c) => (int) $c)->all(),
            ];
        }

        return $out;
    }
}
