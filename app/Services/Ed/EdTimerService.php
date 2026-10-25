<?php

namespace App\Services\Ed;

use App\Models\Clinical\ClinicalAlert;
use App\Models\Ed\EdProtocolRun;
use App\Models\Ed\EdProtocolStep;
use App\Models\Ed\EdVisit;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;

/**
 * Door-to-triage, re-triage and door-to-doctor timers (guideline 26.1 S03/S04): raised through the Alert Service, so they are
 * acknowledged, escalated and reported like every other clinical alert. Safe to run every minute: alerts de-duplicate.
 */
class EdTimerService
{
    public function __construct(
        private readonly AlertService $alerts,
        private readonly EdTriageService $triage,
        private readonly TriageScale $scale,
    ) {}

    /** @return int alerts raised */
    public function check(): int
    {
        $raised = 0;
        $now = now();

        EdVisit::query()->active()->whereNull('doctor_seen_at')->orderBy('id')->each(function (EdVisit $visit) use (&$raised, $now) {
            if ($visit->status === EdVisit::AWAITING_TRIAGE && $visit->triage_due_at?->lte($now)) {
                $raised += $this->raise($visit, 'ed_triage_overdue', 'warning', "{$visit->visit_number} has waited for triage since {$visit->arrival_at->format('H:i')}.", "ed-triage:{$visit->id}");
            }

            if ($visit->status === EdVisit::WAITING) {
                $urgent = $visit->triage_level !== null && $visit->triage_level <= $this->scale->mostUrgent() + 1;

                if ($visit->doctor_due_at?->lte($now)) {
                    $raised += $this->raise($visit, 'ed_door_to_doctor', $urgent ? 'critical' : 'warning', "{$visit->visit_number} (level {$visit->triage_level}) has passed the door-to-doctor target.", "ed-d2d:{$visit->id}");
                }
                if ($visit->retriage_due_at?->lte($now)) {
                    $raised += $this->raise($visit, 'ed_retriage_due', $urgent ? 'critical' : 'warning', "{$visit->visit_number} is due for re-triage.", "ed-retriage:{$visit->id}");
                }
            }
        });

        $raised += $this->checkProtocols();

        return $raised;
    }

    /** A protocol step past its target and not done raises one alert per step. */
    private function checkProtocols(): int
    {
        $raised = 0;

        EdProtocolStep::query()->whereNull('done_at')->whereNotNull('due_at')->where('due_at', '<=', now())
            ->whereHas('run', fn ($q) => $q->where('status', EdProtocolRun::ACTIVE))->with('run.visit')->orderBy('id')
            ->each(function (EdProtocolStep $step) use (&$raised) {
                $visit = $step->run->visit;
                $name = config("ed_protocols.{$step->run->protocol}.name", $step->run->protocol);
                $raised += $this->raise($visit, 'ed_protocol_overdue', 'critical', "{$visit->visit_number} — {$name}: \"{$step->label}\" is overdue.", "ed-protocol:{$step->id}");
            });

        return $raised;
    }

    private function raise(EdVisit $visit, string $category, string $severity, string $message, string $dedup): int
    {
        $before = ClinicalAlert::query()->where('organization_id', $visit->organization_id)->where('dedup_key', $dedup)->count();

        $this->alerts->raise(new AlertRequest(
            organizationId: $visit->organization_id, category: $category, severity: $severity, message: $message, sourceModule: 'ed',
            patientId: $visit->patient_id, branchId: $visit->branch_id, recipientUserIds: $this->triage->roleUsers($visit->organization_id), dedupKey: $dedup,
        ));

        return ClinicalAlert::query()->where('organization_id', $visit->organization_id)->where('dedup_key', $dedup)->count() > $before ? 1 : 0;
    }
}
