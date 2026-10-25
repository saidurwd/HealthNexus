<?php

namespace App\Reports;

use App\Contracts\Reports\ReportDefinition;
use App\Models\Ed\EdVisit;
use App\Services\Reports\ReportContext;
use Illuminate\Support\LazyCollection;

class EdVisitsReport implements ReportDefinition
{
    public function key(): string
    {
        return 'ed.visits';
    }

    public function title(): string
    {
        return 'Emergency visits';
    }

    public function permission(): string
    {
        return 'ed.kpi.view';
    }

    public function filterRules(): array
    {
        return ['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'disposition' => ['nullable', 'string', 'max:20']];
    }

    public function columns(): array
    {
        return [
            ['key' => 'visit_number', 'label' => 'Visit No'],
            ['key' => 'patient', 'label' => 'Patient', 'sensitive' => true],
            ['key' => 'arrival_at', 'label' => 'Arrived'],
            ['key' => 'arrival_mode', 'label' => 'Arrival mode'],
            ['key' => 'triage_level', 'label' => 'Triage level'],
            ['key' => 'door_to_triage_min', 'label' => 'Door to triage (min)'],
            ['key' => 'door_to_doctor_min', 'label' => 'Door to doctor (min)'],
            ['key' => 'length_of_stay_min', 'label' => 'Length of stay (min)'],
            ['key' => 'disposition', 'label' => 'Outcome'],
            ['key' => 'status', 'label' => 'Status'],
        ];
    }

    public function sensitivePermission(): ?string
    {
        return 'patients.export.sensitive';
    }

    public function rows(ReportContext $context): LazyCollection
    {
        return EdVisit::query()->forTenant($context->organizationId, $context->branchId)->with('patient')
            ->when($context->filters['from'] ?? null, fn ($q, $v) => $q->whereDate('arrival_at', '>=', $v))
            ->when($context->filters['to'] ?? null, fn ($q, $v) => $q->whereDate('arrival_at', '<=', $v))
            ->when($context->filters['disposition'] ?? null, fn ($q, $v) => $q->where('disposition_type', $v))
            ->orderBy('id')->cursor()
            ->map(fn (EdVisit $v) => $this->row($v));
    }

    /** @return array<string, scalar|null> */
    private function row(EdVisit $v): array
    {
        return [
            'visit_number' => $v->visit_number,
            'patient' => $v->patient?->full_name,
            'arrival_at' => $v->arrival_at->toDateTimeString(),
            'arrival_mode' => $v->arrival_mode,
            'triage_level' => $v->triage_level,
            'door_to_triage_min' => $v->triaged_at ? (int) round($v->arrival_at->diffInMinutes($v->triaged_at, true)) : null,
            'door_to_doctor_min' => $v->doctor_seen_at ? (int) round($v->arrival_at->diffInMinutes($v->doctor_seen_at, true)) : null,
            'length_of_stay_min' => $v->dispositioned_at ? (int) round($v->arrival_at->diffInMinutes($v->dispositioned_at, true)) : null,
            'disposition' => $v->disposition_type,
            'status' => $v->status,
        ];
    }
}
