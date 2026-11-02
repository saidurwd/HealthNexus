<?php

namespace App\Services\Welfare;

use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfFundEntry;
use App\Models\Welfare\WfGrant;

/** The figures on the welfare home page. */
class WelfareOverview
{
    public function __construct(private readonly FundService $funds) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, int $days = 30): array
    {
        $from = today()->subDays($days - 1);
        $funds = WfFund::query()->where('organization_id', $organizationId)->orderBy('name')->get()->map(fn (WfFund $f) => ['fund' => $f, 'balance' => $this->funds->balance($f), 'committed' => $this->funds->committed($f), 'available' => $this->funds->available($f)]);
        $applied = WfApplication::query()->where('organization_id', $organizationId)->where('status', 'applied')->where('settled_at', '>=', $from);

        return [
            'funds' => $funds,
            'total_available' => number_format((float) $funds->sum(fn ($r) => (float) $r['available']), 2, '.', ''),
            'assessments_waiting' => WfAssessment::query()->where('organization_id', $organizationId)->where('status', 'submitted')->count(),
            'grants_waiting' => WfGrant::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'with_billing' => WfApplication::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'given' => number_format((float) (clone $applied)->sum('amount'), 2, '.', ''),
            'patients_helped' => (clone $applied)->distinct()->count('patient_id'),
            'received' => number_format((float) WfFundEntry::query()->where('organization_id', $organizationId)->where('entry_type', 'donation')->where('occurred_at', '>=', $from)->sum('amount'), 2, '.', ''),
            'days' => $days,
        ];
    }
}
