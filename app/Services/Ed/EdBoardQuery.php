<?php

namespace App\Services\Ed;

use App\Models\Ed\EdVisit;
use App\Models\Ed\EdZone;

/** Read model of the tracking board (template T6): every active patient appears exactly once, most urgent first (guideline 26.1 ED acceptance). */
class EdBoardQuery
{
    public function __construct(private readonly TriageScale $scale) {}

    /** @return array{generated_at: string, counts: array<string, int>, visits: list<array<string, mixed>>, levels: array<int, array>} */
    public function forBranch(int $organizationId, ?int $branchId): array
    {
        $visits = EdVisit::query()->forTenant($organizationId, $branchId)->active()
            ->with(['patient:id,public_id,first_name,middle_name,last_name,sex,date_of_birth,estimated_age,enterprise_patient_no', 'bay:id,code,zone_id', 'zone:id,code,name', 'doctor:id,name', 'nurse:id,name'])
            ->get()
            ->sortBy(fn (EdVisit $v) => [$v->triage_level ?? 99, $v->arrival_at->timestamp])
            ->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'counts' => $visits->countBy('status')->all() + ['total' => $visits->count()],
            'levels' => $this->scale->levels(),
            'visits' => $visits->map(fn (EdVisit $v) => $this->row($v))->all(),
        ];
    }

    private function row(EdVisit $v): array
    {
        $waitMinutes = (int) $v->arrival_at->diffInMinutes(now(), true);
        $breach = $v->doctor_seen_at === null && $v->doctor_due_at !== null && $v->doctor_due_at->isPast();

        return [
            'id' => $v->public_id, 'visit_number' => $v->visit_number, 'status' => $v->status, 'level' => $v->triage_level,
            'patient' => $v->patient?->full_name, 'mrn' => $v->patient?->enterprise_patient_no, 'sex' => $v->patient?->sex,
            'unknown' => $v->is_unknown_patient, 'complaint' => $v->chief_complaint, 'arrival_mode' => $v->arrival_mode,
            'zone' => $v->zone?->name, 'bay' => $v->bay?->code, 'doctor' => $v->doctor?->name, 'nurse' => $v->nurse?->name,
            'wait_minutes' => $waitMinutes, 'arrived_at' => $v->arrival_at->format('H:i'),
            'door_to_doctor_breach' => $breach, 'retriage_due' => $v->retriage_due_at?->isPast() ?? false,
            'triage_overdue' => $v->status === EdVisit::AWAITING_TRIAGE && ($v->triage_due_at?->isPast() ?? false),
            'mass_casualty' => $v->is_mass_casualty,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function bays(int $organizationId, ?int $branchId): array
    {
        return EdZone::query()->forTenant($organizationId, $branchId)->where('is_active', true)->with(['bays' => fn ($q) => $q->where('is_active', true)->with('visit:id,public_id,visit_number,triage_level')])
            ->orderBy('sort_order')->get()
            ->map(fn ($zone) => ['zone' => $zone->name, 'type' => $zone->zone_type, 'bays' => $zone->bays->map(fn ($b) => [
                'id' => $b->public_id, 'code' => $b->code, 'status' => $b->status, 'visit' => $b->visit?->visit_number, 'level' => $b->visit?->triage_level,
            ])->all()])->values()->all();
    }
}
