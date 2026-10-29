<?php

namespace App\Interop\Mappers;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Model;

/** One line of an issued prescription. */
class MedicationRequestMapper extends Mapper
{
    public function type(): string
    {
        return 'MedicationRequest';
    }

    public function sources(): array
    {
        return [['model' => PrescriptionItem::class, 'entity' => 'PrescriptionItem', 'patient' => fn ($q, $pid) => $q->whereIn('prescription_id', Prescription::query()->where('patient_id', $pid)->whereNotNull('issued_at')->select('id')),
            'search' => ['status' => fn ($q, $v) => $q->where('is_active', $v === 'active')]]];
    }

    /** @param PrescriptionItem $m */
    public function toFhir(Model $m): array
    {
        $rx = Prescription::query()->find($m->prescription_id);

        return array_filter($this->base($m, 'PrescriptionItem') + [
            'identifier' => $rx ? [['system' => 'urn:healthnexus:prescription', 'value' => $rx->prescription_no]] : null,
            'status' => $m->is_active ? 'active' : 'stopped',
            'intent' => 'order',
            'medicationCodeableConcept' => ['text' => trim($m->medicine_name.' '.$m->strength.' '.$m->dosage_form)],
            'subject' => $rx ? $this->ref('Patient', Patient::class, $rx->patient_id) : null,
            'encounter' => $rx && $rx->encounter_id ? $this->ref('Encounter', Encounter::class, $rx->encounter_id) : null,
            'authoredOn' => $this->time($rx?->issued_at),
            'dosageInstruction' => [array_filter(['text' => trim(implode(', ', array_filter([$m->frequency, $m->duration ? 'for '.$m->duration : null, $m->instructions])))])],
            'dispenseRequest' => $m->quantity ? ['quantity' => ['value' => (float) $m->quantity]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** @param PrescriptionItem $m */
    public function patientIdOf(Model $m): ?int
    {
        return Prescription::query()->whereKey($m->prescription_id)->value('patient_id');
    }
}
