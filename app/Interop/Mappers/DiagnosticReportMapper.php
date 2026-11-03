<?php

namespace App\Interop\Mappers;

use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Patient;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyReport;
use App\Models\Radiology\RadiologyStudy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Final and amended laboratory and radiology reports. Drafts are never served. */
class DiagnosticReportMapper extends Mapper
{
    public function type(): string
    {
        return 'DiagnosticReport';
    }

    public function sources(): array
    {
        return [
            ['model' => LabReport::class, 'entity' => 'LabReport', 'patient' => fn ($q, $pid) => $q->whereIn('status', ['final', 'amended'])->whereIn('lab_order_id', LabOrder::query()->where('patient_id', $pid)->select('id')),
                'search' => ['category' => fn ($q, $v) => $v === 'LAB' ? $q : $q->whereRaw('1 = 0'), 'date' => fn ($q, $v) => $q->whereDate('generated_at', $v)]],
            ['model' => RadiologyReport::class, 'entity' => 'RadiologyReport', 'patient' => fn ($q, $pid) => $q->whereIn('status', ['final', 'amended'])->where('is_current', true)->whereIn('examination_id', RadiologyExamination::query()->where('patient_id', $pid)->select('id')),
                'search' => ['category' => fn ($q, $v) => $v === 'RAD' ? $q : $q->whereRaw('1 = 0'), 'date' => fn ($q, $v) => $q->whereDate('approved_at', $v)]],
        ];
    }

    /** @param LabReport|RadiologyReport $m */
    public function toFhir(Model $m): array
    {
        return $m instanceof LabReport ? $this->lab($m) : $this->radiology($m);
    }

    private function lab(LabReport $r): array
    {
        $order = LabOrder::query()->find($r->lab_order_id);
        $results = LabResult::query()->whereIn('lab_order_item_id', DB::table('lab_order_items')->where('lab_order_id', $r->lab_order_id)->select('id'))->where('is_current', true)->orderBy('id')->get();

        return array_filter($this->base($r, 'LabReport') + [
            'identifier' => [['system' => 'urn:healthnexus:lab-report', 'value' => $r->report_number]],
            'status' => $r->status === 'amended' ? 'amended' : 'final',
            'category' => [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/v2-0074', 'LAB', 'Laboratory')]]],
            'code' => ['text' => 'Laboratory report '.$r->report_number],
            'subject' => $order ? $this->ref('Patient', Patient::class, $order->patient_id) : null,
            'encounter' => $order && $order->encounter_id ? $this->ref('Encounter', Encounter::class, $order->encounter_id) : null,
            'issued' => $this->time($r->generated_at),
            'result' => $results->map(fn (LabResult $x) => ['reference' => 'Observation/'.$this->ids->expose($x, 'LabResult')])->values()->all(),
        ], fn ($v) => $v !== null && $v !== []);
    }

    private function radiology(RadiologyReport $r): array
    {
        $exam = RadiologyExamination::query()->find($r->examination_id);
        $study = $exam ? RadiologyStudy::query()->where('radiology_examination_id', $exam->id)->first() : null;

        return array_filter($this->base($r, 'RadiologyReport') + [
            'identifier' => [['system' => 'urn:healthnexus:rad-report', 'value' => $r->report_number]],
            'status' => $r->status === 'amended' ? 'amended' : 'final',
            'category' => [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/v2-0074', 'RAD', 'Radiology')]]],
            'code' => ['text' => 'Radiology report '.$r->report_number],
            'subject' => $exam ? $this->ref('Patient', Patient::class, $exam->patient_id) : null,
            'issued' => $this->time($r->approved_at),
            'imagingStudy' => $study ? [['reference' => 'ImagingStudy/'.$this->ids->expose($study)]] : null,
            'conclusion' => $r->impression,
            'presentedForm' => $r->findings ? [['contentType' => 'text/plain', 'title' => 'Findings', 'data' => base64_encode((string) $r->findings)]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** @param LabReport|RadiologyReport $m */
    public function patientIdOf(Model $m): ?int
    {
        return $m instanceof LabReport ? LabOrder::query()->whereKey($m->lab_order_id)->value('patient_id') : RadiologyExamination::query()->whereKey($m->examination_id)->value('patient_id');
    }
}
