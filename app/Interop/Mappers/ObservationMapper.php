<?php

namespace App\Interop\Mappers;

use App\Models\Clinical\ClinicalObservation;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Bedside measurements (the Observation Service's store) and laboratory results. */
class ObservationMapper extends Mapper
{
    public function type(): string
    {
        return 'Observation';
    }

    public function sources(): array
    {
        return [
            ['model' => ClinicalObservation::class, 'entity' => 'ClinicalObservation', 'patient' => 'patient_id', 'search' => [
                'code' => fn ($q, $v) => $q->where('code', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v),
                'category' => fn ($q, $v) => $v === 'vital-signs' ? $q : $q->whereRaw('1 = 0'),
                'date' => fn ($q, $v) => $q->whereDate('effective_at', $v),
            ]],
            ['model' => LabResult::class, 'entity' => 'LabResult',
                'patient' => fn ($q, $pid) => $q->whereIn('lab_order_item_id', DB::table('lab_order_items')->whereIn('lab_order_id', LabOrder::query()->where('patient_id', $pid)->select('id'))->select('id'))->where('is_current', true),
                'search' => [
                    'category' => fn ($q, $v) => $v === 'laboratory' ? $q : $q->whereRaw('1 = 0'),
                    'code' => fn ($q, $v) => $q->whereIn('test_id', LabTest::query()->where('code', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v)->select('id')),
                    'date' => fn ($q, $v) => $q->whereDate('reported_at', $v),
                ]],
        ];
    }

    /** @param ClinicalObservation|LabResult $m */
    public function toFhir(Model $m): array
    {
        return $m instanceof ClinicalObservation ? $this->vital($m) : $this->lab($m);
    }

    private function vital(ClinicalObservation $m): array
    {
        $loinc = $this->terminology->uri('loinc', 'http://loinc.org');
        $ucum = $this->terminology->uri('ucum', 'http://unitsofmeasure.org');

        return array_filter($this->base($m, 'ClinicalObservation') + [
            'status' => match ($m->status) {
                'entered_in_error' => 'entered-in-error', 'preliminary' => 'preliminary', 'amended' => 'amended', default => 'final'
            },
            'category' => [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/observation-category', 'vital-signs', 'Vital Signs')]]],
            'code' => ['coding' => [$this->coding($loinc, $m->code, $m->display)], 'text' => $m->display],
            'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
            'encounter' => $m->encounter_id ? $this->ref('Encounter', Encounter::class, $m->encounter_id) : null,
            'effectiveDateTime' => $this->time($m->effective_at),
            'issued' => $this->time($m->recorded_at),
            'valueQuantity' => $m->value_number !== null ? array_filter(['value' => (float) $m->value_number, 'unit' => $m->unit_code, 'system' => $ucum, 'code' => $m->unit_code]) : null,
            'valueString' => $m->value_number === null && $m->value_text !== null ? $m->value_text : null,
            'valueBoolean' => $m->value_boolean,
            'interpretation' => $m->interpretation ? [['text' => $m->interpretation]] : null,
            'referenceRange' => $m->reference_low !== null || $m->reference_high !== null ? [array_filter(['low' => $m->reference_low !== null ? ['value' => (float) $m->reference_low] : null, 'high' => $m->reference_high !== null ? ['value' => (float) $m->reference_high] : null])] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    private function lab(LabResult $r): array
    {
        $test = LabTest::query()->whereKey($r->test_id)->first(['code', 'name', 'unit']);
        $order = LabOrder::query()->whereKey(DB::table('lab_order_items')->where('id', $r->lab_order_item_id)->value('lab_order_id'))->first(['id', 'patient_id', 'encounter_id']);
        $loinc = $this->terminology->translate('lab-test', (string) $test?->code, 'loinc')[0] ?? null;
        $coding = array_values(array_filter([$loinc ? $this->coding($loinc['system'], $loinc['code'], $test?->name) : null, $this->coding('urn:healthnexus:lab-test', (string) $test?->code, $test?->name)]));
        $flag = $r->critical_flag ? 'AA' : ($r->abnormal_flag ? strtoupper(substr((string) $r->abnormal_flag, 0, 2)) : null);

        return array_filter($this->base($r, 'LabResult') + [
            'status' => match ($r->result_status) {
                'amended' => 'amended', 'final', 'reported' => 'final', 'cancelled' => 'cancelled', default => 'preliminary'
            },
            'category' => [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/observation-category', 'laboratory', 'Laboratory')]]],
            'code' => ['coding' => $coding, 'text' => $test?->name],
            'subject' => $order ? $this->ref('Patient', Patient::class, $order->patient_id) : null,
            'encounter' => $order && $order->encounter_id ? $this->ref('Encounter', Encounter::class, $order->encounter_id) : null,
            'effectiveDateTime' => $this->time($r->reported_at ?? $r->entered_at),
            'issued' => $this->time($r->reported_at),
            'valueQuantity' => $r->numeric_value !== null ? array_filter(['value' => (float) $r->numeric_value, 'unit' => $r->unit ?: $test?->unit]) : null,
            'valueString' => $r->numeric_value === null ? ($r->qualitative_value ?? $r->text_value) : null,
            'interpretation' => $flag ? [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation', $flag)]]] : null,
            'referenceRange' => $r->reference_range_low !== null || $r->reference_range_text ? [array_filter(['low' => $r->reference_range_low !== null ? ['value' => (float) $r->reference_range_low] : null, 'high' => $r->reference_range_high !== null ? ['value' => (float) $r->reference_range_high] : null, 'text' => $r->reference_range_text])] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** @param ClinicalObservation|LabResult $m */
    public function patientIdOf(Model $m): ?int
    {
        if ($m instanceof ClinicalObservation) {
            return (int) $m->patient_id;
        }

        return LabOrder::query()->whereKey(DB::table('lab_order_items')->where('id', $m->lab_order_item_id)->value('lab_order_id'))->value('patient_id');
    }
}
