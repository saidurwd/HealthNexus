<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmComplaintEvent;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Complaints (S11): received, assigned, investigated, answered, closed — each step in an append-only trail, each with a deadline by severity. Anything past its deadline is
 * escalated by the daily check. The link to the quality module (Phase 21) is the number; nothing is copied.
 */
class ComplaintService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    public function lodge(int $organizationId, array $d, ?User $by = null, ?Patient $patient = null): CrmComplaint
    {
        if (! in_array($d['category'] ?? '', (array) $this->policy->get('crm.complaint_categories', []), true) || trim((string) ($d['description'] ?? '')) === '') {
            throw ValidationException::withMessages(['description' => __('Tell us what happened.')]);
        }
        $severity = in_array($d['severity'] ?? 'medium', ['low', 'medium', 'high'], true) ? ($d['severity'] ?? 'medium') : 'medium';
        $hours = (int) (((array) $this->policy->get('crm.complaint_due_hours', []))[$severity] ?? 72);

        return DB::transaction(function () use ($organizationId, $d, $by, $patient, $severity, $hours) {
            $c = CrmComplaint::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'patient_id' => $patient->id ?? ($d['patient_id'] ?? null), 'number' => $this->numbers->next('crm.complaint', $organizationId, null, prefix: 'CMP', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
                'channel' => $d['channel'] ?? 'portal', 'category' => $d['category'], 'severity' => $severity, 'description' => mb_substr(strip_tags((string) $d['description']), 0, 4000), 'status' => 'open', 'due_at' => now()->addHours($hours)]);
            $this->event($c, 'received', null, $by);
            $this->audit->log('CREATE', CrmComplaint::class, $c->id, null, ['number' => $c->number, 'severity' => $severity]);

            return $c;
        });
    }

    public function assign(CrmComplaint $c, User $to, User $by): CrmComplaint
    {
        $this->open($c);
        $c->update(['assigned_to' => $to->id, 'status' => $c->status === 'open' ? 'assigned' : $c->status]);
        $this->event($c, 'assigned', $to->name, $by);

        return $c;
    }

    public function investigate(CrmComplaint $c, User $by, ?string $note): CrmComplaint
    {
        $this->open($c);
        $c->update(['status' => 'investigating', 'assigned_to' => $c->assigned_to ?: $by->id]);
        $this->event($c, 'investigating', $note, $by);

        return $c;
    }

    public function resolve(CrmComplaint $c, User $by, string $resolution): CrmComplaint
    {
        $this->open($c);
        if (trim($resolution) === '') {
            throw ValidationException::withMessages(['resolution' => __('Write what was done about it.')]);
        }
        $c->update(['status' => 'resolved', 'resolution' => mb_substr($resolution, 0, 500), 'resolved_at' => now()]);
        $this->event($c, 'resolved', $resolution, $by);

        return $c;
    }

    public function close(CrmComplaint $c, User $by): CrmComplaint
    {
        if ($c->status !== 'resolved') {
            throw new PortalException(__('Only a resolved complaint can be closed.'), 409);
        }
        $c->update(['status' => 'closed', 'closed_by' => $by->id, 'closed_at' => now()]);
        $this->event($c, 'closed', null, $by);

        return $c;
    }

    /** The complainant says whether they are satisfied with the answer (once). */
    public function satisfaction(CrmComplaint $c, int $score): CrmComplaint
    {
        if (! in_array($c->status, ['resolved', 'closed'], true) || $c->satisfaction !== null) {
            throw new PortalException(__('This cannot be rated now.'), 409);
        }
        if ($score < 1 || $score > 5) {
            throw ValidationException::withMessages(['satisfaction' => __('Choose a score from 1 to 5.')]);
        }
        $c->update(['satisfaction' => $score]);
        $this->event($c, 'rated', (string) $score, null);

        return $c;
    }

    /** Anything open past its deadline is marked escalated, once. */
    public function escalateOverdue(): int
    {
        $n = 0;
        CrmComplaint::query()->whereIn('status', ['open', 'assigned', 'investigating'])->where('due_at', '<', now())->orderBy('id')->each(function (CrmComplaint $c) use (&$n) {
            $c->update(['status' => 'escalated']);
            $this->event($c, 'escalated', 'past its deadline', null);
            $n++;
        });

        return $n;
    }

    private function open(CrmComplaint $c): void
    {
        if (in_array($c->status, ['closed'], true)) {
            throw new PortalException(__('This complaint is closed.'), 409);
        }
    }

    private function event(CrmComplaint $c, string $kind, ?string $note, ?User $by): void
    {
        CrmComplaintEvent::create(['complaint_id' => $c->id, 'kind' => $kind, 'note' => $note ? mb_substr($note, 0, 500) : null, 'user_id' => $by?->id, 'created_at' => now()]);
    }
}
