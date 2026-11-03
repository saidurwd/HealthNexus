<?php

namespace App\Services\Ed;

use App\Events\Ed\EdTriageCompleted;
use App\Models\Ed\EdTriageRecord;
use App\Models\Ed\EdVisit;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Triage and re-triage. Vital signs are written ONLY through the Observation Service (guideline 26.1 override 1). */
class EdTriageService
{
    public function __construct(
        private readonly TriageScale $scale,
        private readonly EdVitalsRecorder $vitals,
        private readonly AlertService $alerts,
        private readonly EdVisitService $visits,
    ) {}

    /**
     * @param  array{level: int, complaint?: ?string, discriminators?: list<string>, notes?: ?string, vitals?: array<string, mixed>}  $data
     */
    public function triage(EdVisit $visit, User $nurse, array $data): EdTriageRecord
    {
        return DB::transaction(function () use ($visit, $nurse, $data) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);

            if (! $locked->isActive()) {
                throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
            }

            $level = (int) $data['level'];
            $definition = $this->scale->level($level);
            $isRetriage = $locked->triaged_at !== null;
            $previousLevel = $locked->triage_level;

            $setId = $this->vitals->record($locked, $nurse, $data['vitals'] ?? []);

            $record = EdTriageRecord::create([
                'organization_id' => $locked->organization_id, 'visit_id' => $locked->id, 'level' => $level, 'is_retriage' => $isRetriage,
                'complaint' => $data['complaint'] ?? $locked->chief_complaint, 'discriminators' => $data['discriminators'] ?? [], 'notes' => $data['notes'] ?? null,
                'observation_set_id' => $setId, 'assessed_by' => $nurse->id, 'assessed_at' => now(),
            ]);

            $from = $locked->status;
            $update = [
                'triage_level' => $level, 'retriage_due_at' => now()->addMinutes($definition['reassess']),
                'chief_complaint' => $data['complaint'] ?? $locked->chief_complaint,
            ];
            if (! $isRetriage) {
                $update += ['triaged_at' => now(), 'doctor_due_at' => $locked->arrival_at->copy()->addMinutes($definition['door_to_doctor'])];
            } elseif ($previousLevel !== $level && $locked->doctor_seen_at === null) {
                // A changed level moves the door-to-doctor target; it is measured from arrival, never reset.
                $update['doctor_due_at'] = $locked->arrival_at->copy()->addMinutes($definition['door_to_doctor']);
            }
            if ($locked->status === EdVisit::AWAITING_TRIAGE) {
                $update['status'] = EdVisit::WAITING;
            }
            $locked->update($update);

            $locked->status !== $from && $this->visits->recordStatus($locked, $from, $locked->status, $nurse, 'Triaged: level '.$level);

            $this->alertIfMoreUrgent($locked, $nurse, $level, $previousLevel);

            EdTriageCompleted::dispatch($locked->id, $locked->organization_id, $locked->patient_id);

            return $record;
        });
    }

    private function alertIfMoreUrgent(EdVisit $visit, User $nurse, int $level, ?int $previous): void
    {
        $mostUrgent = $this->scale->mostUrgent();
        if ($level !== $mostUrgent || $previous === $mostUrgent) {
            return;
        }

        $this->alerts->raise(new AlertRequest(
            organizationId: $visit->organization_id, category: 'ed_resuscitation', severity: 'critical',
            message: "Triage level {$level} ({$this->scale->level($level)['name']}): {$visit->visit_number} needs a doctor now.",
            sourceModule: 'ed', patientId: $visit->patient_id, branchId: $visit->branch_id, sourceType: EdVisit::class, sourceId: $visit->id,
            recipientUserIds: $this->roleUsers($visit->organization_id), dedupKey: "ed-level1:{$visit->id}", raisedBy: $nurse->id,
        ));
    }

    /** @return list<int> */
    public function roleUsers(int $organizationId): array
    {
        return User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', config('ed.alert_roles')))->limit(30)->pluck('id')->all();
    }
}
