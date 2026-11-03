<?php

namespace App\Services\Facility;

use App\Models\Facility\FacCleaningTask;
use App\Models\Organization;
use App\Services\ModuleManager;

/** The daily round: work orders for plans that fall due, calibration and warranty warnings, packs past their date, diets and passes of patients who went home, and deaths that need a mortuary case. One hospital's failure does not stop the rest. */
class FacilityCheck
{
    public function __construct(private readonly MaintenanceService $maintenance, private readonly CssdService $cssd, private readonly DietService $diet, private readonly VisitorService $visitors, private readonly MortuaryService $mortuary,
        private readonly FacilityAlerts $alerts, private readonly FacilityPolicy $policy, private readonly ModuleManager $modules) {}

    /** @return array<string,mixed> */
    public function run(int $organizationId): array
    {
        $out = ['work_orders_opened' => $this->maintenance->openDue($organizationId)];
        $out += $this->maintenance->alertDue($organizationId);
        $out['packs_withdrawn'] = $this->cssd->withdrawExpired($organizationId);
        $out['diets_ended'] = $this->diet->endForDischarged($organizationId);
        $out['passes_ended'] = $this->visitors->expireEnded($organizationId);
        $out['mortuary_cases_opened'] = $this->mortuary->sweep($organizationId);
        $late = FacCleaningTask::query()->where('organization_id', $organizationId)->whereIn('status', ['pending', 'in_progress'])->where('requested_at', '<', now()->subMinutes(2 * $this->policy->int('housekeeping.turnover_target_minutes', 45)))->count();
        $out['cleaning_late'] = $late;
        $late > 0 && $this->alerts->raise($organizationId, 'warning', __(':n cleaning task(s) have waited more than twice the target.', ['n' => $late]), FacCleaningTask::class, 0, 'fac-clean-late-'.$organizationId.'-'.now()->format('YmdH'), 'facility.housekeeping.manage');

        return $out;
    }

    /** @return int hospitals checked */
    public function runAll(): int
    {
        $n = 0;
        foreach (Organization::query()->where('is_active', true)->pluck('id') as $id) {
            if (! $this->modules->isEnabled('facility', (int) $id)) {
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
