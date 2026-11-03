<?php

namespace App\Services\Welfare;

use App\Models\Organization;
use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use App\Services\ModuleManager;

/** The daily round: assessments and grants past their date lapse, discounts Billing approved are charged to the fund if the event was missed, and things waiting too long or funds running low are raised to the people who can act. */
class WelfareCheck
{
    public function __construct(private readonly AssessmentService $assessments, private readonly GrantService $grants, private readonly ApplicationService $applications, private readonly FundService $funds,
        private readonly WelfareAlerts $alerts, private readonly WelfarePolicy $policy, private readonly ModuleManager $modules) {}

    /** @return array<string,int> */
    public function run(int $organizationId): array
    {
        $out = ['assessments_expired' => $this->assessments->expireDue($organizationId), 'grants_expired' => $this->grants->expireDue($organizationId), 'settled' => $this->applications->reconcile($organizationId)];
        $before = now()->subDays($this->policy->int('waiting_days', 3));
        $a = WfAssessment::query()->where('organization_id', $organizationId)->where('status', 'submitted')->where('updated_at', '<', $before)->count();
        $a > 0 && $this->alerts->raise($organizationId, 'info', __(':n assessment(s) have waited for a decision for days.', ['n' => $a]), WfAssessment::class, 0, 'wf-assess-wait-'.$organizationId.'-'.today()->format('Ymd'), 'welfare.assessment.decide');
        $g = WfGrant::query()->where('organization_id', $organizationId)->where('status', 'requested')->where('updated_at', '<', $before)->count();
        $g > 0 && $this->alerts->raise($organizationId, 'info', __(':n grant(s) have waited for approval for days.', ['n' => $g]), WfGrant::class, 0, 'wf-grant-wait-'.$organizationId.'-'.today()->format('Ymd'), 'welfare.grant.approve');
        $b = WfApplication::query()->where('organization_id', $organizationId)->where('status', 'requested')->where('requested_at', '<', $before)->count();
        $b > 0 && $this->alerts->raise($organizationId, 'warning', __(':n welfare discount(s) are still waiting for Billing to approve.', ['n' => $b]), WfApplication::class, 0, 'wf-bill-wait-'.$organizationId.'-'.today()->format('Ymd'), 'welfare.grant.apply');
        $low = 0;
        foreach (WfFund::query()->where('organization_id', $organizationId)->where('status', 'active')->where('low_balance_alert', '>', 0)->get() as $f) {
            if (bccomp($this->funds->available($f), (string) $f->low_balance_alert, 2) < 0) {
                $low++;
                $this->alerts->raise($organizationId, 'warning', __('Fund :f has only :a free to give.', ['f' => $f->name, 'a' => $this->funds->available($f)]), WfFund::class, (int) $f->id, 'wf-low-'.$f->id.'-'.today()->format('Ym'), 'welfare.fund.manage');
            }
        }
        $out += ['assessments_waiting' => $a, 'grants_waiting' => $g, 'with_billing_waiting' => $b, 'funds_low' => $low];

        return $out;
    }

    /** @return int hospitals checked */
    public function runAll(): int
    {
        $n = 0;
        foreach (Organization::query()->where('is_active', true)->pluck('id') as $id) {
            if (! $this->modules->isEnabled('welfare', (int) $id)) {
                continue;
            }
            try {
                $this->run((int) $id);
                $n++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $n;
    }
}
