<?php

namespace App\Services\Quality;

use App\Models\Organization;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaMortalityReview;

/** The daily round: find what other modules recorded, work out the indicators, and tell people about whatever is late or about to be. One organization's failure does not stop the rest. */
class QualityCheck
{
    public function __construct(private readonly IncidentSweep $sweep, private readonly MortalityService $mortality, private readonly IndicatorService $indicators, private readonly ActionService $actions,
        private readonly DocumentService $documents, private readonly ComplianceService $compliance, private readonly PrivilegeService $privileges, private readonly QualityAlerts $alerts, private readonly QualityPolicy $policy) {}

    /** @return array<string,int|array<string,int>> */
    public function run(int $organizationId, ?int $sweepDays = null): array
    {
        $out = [];
        $out['incidents_found'] = array_sum($this->sweep->run($organizationId, $sweepDays));
        $out['deaths_found'] = $this->mortality->sweep($organizationId);
        $out['indicator_values'] = $this->indicators->computeRecent($organizationId, 3);
        $out['actions_overdue'] = $this->actions->alertOverdue($organizationId);
        $out['documents_due'] = $this->documents->alertReviews($organizationId);
        $out['compliance_due'] = $this->compliance->alertDue($organizationId);
        $out['privileges'] = $this->privileges->maintain($organizationId);
        $out['incidents_overdue'] = $this->alertLateIncidents($organizationId);
        $out['reviews_overdue'] = $this->alertLateReviews($organizationId);

        return $out;
    }

    /** @return int organizations checked */
    public function runAll(?int $sweepDays = null): int
    {
        $n = 0;
        foreach (Organization::query()->where('is_active', true)->pluck('id') as $id) {
            try {
                $this->run((int) $id, $sweepDays);
                $n++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $n;
    }

    private function alertLateIncidents(int $org): int
    {
        $n = QaIncident::query()->where('organization_id', $org)->where('status', '!=', 'closed')->where('due_on', '<', today()->toDateString())->count();
        $n > 0 && $this->alerts->raise($org, 'warning', __(':n incident(s) are past their due date.', ['n' => $n]), QaIncident::class, 0, 'qa-late-incidents-'.$org.'-'.today()->toDateString(), 'quality.incident.triage', false);

        return $n;
    }

    private function alertLateReviews(int $org): int
    {
        $days = $this->policy->int('indicator.mortality_review_days', 30);
        $n = QaMortalityReview::query()->where('organization_id', $org)->where('status', 'pending')->where('died_at', '<', now()->subDays($days))->count();
        $n > 0 && $this->alerts->raise($org, 'warning', __(':n death review(s) are past :d days.', ['n' => $n, 'd' => $days]), QaMortalityReview::class, 0, 'qa-late-reviews-'.$org.'-'.today()->toDateString(), 'quality.mortality.review', false);

        return $n;
    }
}
