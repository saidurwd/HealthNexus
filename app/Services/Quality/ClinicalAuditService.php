<?php

namespace App\Services\Quality;

use App\Models\Quality\QaAudit;
use App\Models\Quality\QaAuditFinding;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Clinical audit: choose a standard, look at a sample, count how often each criterion was met, and turn the gaps into actions. */
class ClinicalAuditService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly QualityPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d title, standard, department_id, audited_on, sample_size, branch_id */
    public function create(int $organizationId, array $d, User $by): QaAudit
    {
        if (mb_strlen(trim((string) ($d['title'] ?? ''))) < 5) {
            throw ValidationException::withMessages(['title' => __('Give the audit a title.')]);
        }
        $audit = QaAudit::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'number' => $this->numbers->next('qa.audit', $organizationId, null, prefix: 'AUD', format: '{PREFIX}-{YEAR}-{SEQ:4}', period: NumberSeriesService::PERIOD_YEAR),
            'title' => mb_substr(trim($d['title']), 0, 200), 'standard' => isset($d['standard']) ? mb_substr(trim($d['standard']), 0, 500) : null, 'department_id' => $d['department_id'] ?? null, 'auditor_id' => $by->id,
            'audited_on' => $d['audited_on'] ?? today()->toDateString(), 'sample_size' => (int) ($d['sample_size'] ?? 0), 'status' => 'in_progress']);
        $this->audit->log('CREATE', QaAudit::class, $audit->id, null, ['number' => $audit->number]);

        return $audit;
    }

    /** @param list<array<string,mixed>> $rows each with criterion, met, not_met and optionally comment */
    public function record(QaAudit $audit, array $rows): QaAudit
    {
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['status' => __('This audit is complete.')]);
        }
        DB::transaction(function () use ($audit, $rows) {
            QaAuditFinding::query()->where('audit_id', $audit->id)->delete();
            foreach ($rows as $r) {
                $criterion = trim((string) ($r['criterion'] ?? ''));
                if ($criterion === '') {
                    continue;
                }
                $met = max(0, (int) ($r['met'] ?? 0));
                $not = max(0, (int) ($r['not_met'] ?? 0));
                QaAuditFinding::create(['audit_id' => $audit->id, 'criterion' => mb_substr($criterion, 0, 300), 'met' => $met, 'not_met' => $not, 'comment' => isset($r['comment']) ? mb_substr((string) $r['comment'], 0, 500) : null]);
            }
        });

        return $audit;
    }

    public function complete(QaAudit $audit, User $by, ?string $summary): QaAudit
    {
        $findings = $audit->findings()->get();
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['status' => __('This audit is complete.')]);
        }
        if ($findings->isEmpty()) {
            throw ValidationException::withMessages(['findings' => __('Record at least one criterion first.')]);
        }
        $met = (int) $findings->sum('met');
        $all = $met + (int) $findings->sum('not_met');
        $audit->update(['status' => 'completed', 'compliance' => $all > 0 ? round(100 * $met / $all, 2) : null, 'summary' => $summary ? mb_substr(strip_tags($summary), 0, 4000) : null]);
        $this->audit->log('UPDATE', QaAudit::class, $audit->id, null, ['status' => 'completed']);

        return $audit;
    }

    /** Criteria met less often than the threshold. @return list<array{criterion:string,compliance:float}> */
    public function gaps(QaAudit $audit): array
    {
        $limit = (float) $this->policy->get('audit.gap_threshold', 90);
        $out = [];
        foreach ($audit->findings as $f) {
            $total = $f->met + $f->not_met;
            $c = $total > 0 ? round(100 * $f->met / $total, 1) : 100.0;
            if ($c < $limit) {
                $out[] = ['criterion' => $f->criterion, 'compliance' => $c];
            }
        }

        return $out;
    }
}
