<?php

namespace App\Reports;

use App\Contracts\Reports\ReportDefinition;
use App\Models\Patient;
use App\Services\Reports\ReportContext;
use Illuminate\Support\LazyCollection;

class PatientListReport implements ReportDefinition
{
    public function key(): string
    {
        return 'patients.list';
    }

    public function title(): string
    {
        return 'Patient list';
    }

    public function permission(): string
    {
        return 'patients.export';
    }

    public function filterRules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:30'],
            'registered_from' => ['nullable', 'date'],
            'registered_to' => ['nullable', 'date', 'after_or_equal:registered_from'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'enterprise_patient_no', 'label' => 'Patient No'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'date_of_birth', 'label' => 'Date of birth'],
            ['key' => 'sex', 'label' => 'Sex'],
            ['key' => 'phone', 'label' => 'Phone', 'sensitive' => true],
            ['key' => 'national_identifier', 'label' => 'National ID', 'sensitive' => true],
            ['key' => 'registered_at', 'label' => 'Registered at'],
            ['key' => 'status', 'label' => 'Status'],
        ];
    }

    public function sensitivePermission(): ?string
    {
        return 'patients.export.sensitive';
    }

    public function rows(ReportContext $context): LazyCollection
    {
        return Patient::query()
            ->where('organization_id', $context->organizationId)
            ->when($context->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($context->filters['registered_from'] ?? null, fn ($q, $v) => $q->whereDate('registered_at', '>=', $v))
            ->when($context->filters['registered_to'] ?? null, fn ($q, $v) => $q->whereDate('registered_at', '<=', $v))
            ->orderBy('id')
            ->cursor()
            ->map(fn (Patient $p) => [
                'enterprise_patient_no' => $p->enterprise_patient_no,
                'name' => trim(implode(' ', array_filter([$p->first_name, $p->middle_name, $p->last_name]))),
                'date_of_birth' => $p->date_of_birth?->toDateString(),
                'sex' => $p->sex,
                'phone' => $p->phone,
                'national_identifier' => $p->national_identifier,
                'registered_at' => $p->registered_at?->toDateTimeString(),
                'status' => $p->status,
            ]);
    }
}
