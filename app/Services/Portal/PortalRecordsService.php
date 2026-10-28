<?php

namespace App\Services\Portal;

use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientProblem;
use App\Models\Portal\PortalResultRelease;
use App\Models\Prescription;
use App\Models\Radiology\RadiologyReport;
use App\Models\Radiology\RadiologyStudy;
use App\Models\User;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the patient is shown of their own record (S03, S04, S05). Everything here READS the owning module's tables; nothing is copied, and a result is only ever reached through a
 * `portal_result_releases` row that says "released" (guideline override 1 and 2).
 */
class PortalRecordsService
{
    /** @return array<string, mixed> */
    public function summary(Patient $patient): array
    {
        return [
            'allergies' => PatientAllergy::query()->where('patient_id', $patient->id)->where('is_active', true)->orderBy('substance')->get(['substance', 'severity', 'reaction']),
            'problems' => PatientProblem::query()->where('patient_id', $patient->id)->where('status', 'active')->orderBy('problem_name')->get(['problem_name', 'onset_date']),
            'vitals' => VitalSign::query()->where('patient_id', $patient->id)->latest('recorded_at')->first(['recorded_at', 'temperature', 'temperature_unit', 'systolic', 'diastolic', 'pulse_rate', 'height', 'weight', 'bmi']),
            'visits' => $this->visits($patient->id, 3),
        ];
    }

    /** @return Collection<int, array{public_id: string, date: Carbon|null, type: string, doctor: mixed, diagnoses: array<mixed>}> */
    public function visits(int $patientId, int $limit = 20): Collection
    {
        return Encounter::query()->where('patient_id', $patientId)->whereNotNull('completed_at')->orderByDesc('encounter_date')->limit($limit)->get()->map(fn (Encounter $e): array => [
            'public_id' => $e->public_id, 'date' => $e->encounter_date, 'type' => $e->encounter_type, 'doctor' => $e->provider_id ? User::query()->whereKey($e->provider_id)->value('name') : null,
            'diagnoses' => Diagnosis::query()->where('encounter_id', $e->id)->where('status', '!=', 'entered_in_error')->orderByDesc('is_primary')->pluck('description')->all(),
        ])->values();
    }

    /**
     * Hospital stays and emergency visits (S07). What the patient is told on leaving (diagnosis, instructions, follow-up date) appears only once the stay is finished.
     *
     * @return list<array<string, mixed>>
     */
    public function stays(int $patientId): array
    {
        $admissions = DB::table('ipd_admissions as a')->leftJoin('ipd_discharge_requests as d', fn ($j) => $j->on('d.admission_id', '=', 'a.id')->whereRaw('d.id = (select max(x.id) from ipd_discharge_requests x where x.admission_id = a.id)'))
            ->where('a.patient_id', $patientId)->orderByDesc('a.admitted_at')->limit(20)
            ->get(['a.admission_number', 'a.admitted_at', 'a.actual_discharge_date', 'a.status', 'd.discharge_diagnosis', 'd.instructions', 'd.follow_up_date'])
            ->map(function ($r): array {
                $done = $r->status === 'discharged';

                return ['kind' => 'admission', 'number' => $r->admission_number, 'from' => (string) $r->admitted_at, 'to' => $r->actual_discharge_date ? (string) $r->actual_discharge_date : null, 'status' => $r->status,
                    'diagnosis' => $done ? $r->discharge_diagnosis : null, 'instructions' => $done ? $r->instructions : null, 'follow_up' => $done && $r->follow_up_date ? (string) $r->follow_up_date : null];
            });
        $emergency = DB::table('ed_visits as v')->leftJoin('ed_dispositions as d', 'd.visit_id', '=', 'v.id')->where('v.patient_id', $patientId)->orderByDesc('v.arrival_at')->limit(20)
            ->get(['v.visit_number', 'v.arrival_at', 'v.chief_complaint', 'v.status', 'd.type as disposition'])
            ->map(fn ($r): array => ['kind' => 'emergency', 'number' => $r->visit_number, 'from' => (string) $r->arrival_at, 'to' => null, 'status' => $r->status, 'diagnosis' => null, 'instructions' => null, 'follow_up' => null, 'reason' => $r->chief_complaint, 'outcome' => $r->disposition]);

        return $admissions->concat($emergency)->sortByDesc('from')->values()->all();
    }

    /** @return list<array<string, mixed>> insurance on file: the number is shown masked */
    public function insurance(int $patientId): array
    {
        return DB::table('billing_insurance_policies as p')->leftJoin('insurance_plans as pl', 'pl.id', '=', 'p.plan_id')->where('p.patient_id', $patientId)->orderBy('p.coverage_order')
            ->get(['p.policy_number', 'p.status', 'p.effective_from', 'p.effective_to', 'pl.name as plan'])
            ->map(fn ($r): array => ['plan' => $r->plan, 'policy' => '••••'.substr((string) $r->policy_number, -4), 'status' => $r->status, 'from' => $r->effective_from, 'to' => $r->effective_to])->values()->all();
    }

    /** @return Collection<int, PortalResultRelease> only what has been released */
    public function results(array $patientIds, int $limit = 50): Collection
    {
        return PortalResultRelease::query()->whereIn('patient_id', $patientIds)->where('status', 'released')->orderByDesc('released_at')->limit($limit)->get();
    }

    /** The patient's own list also shows that something exists but is not released (no content), so the silence is not mistaken for "no result". */
    public function pendingNotice(array $patientIds): Collection
    {
        return PortalResultRelease::query()->whereIn('patient_id', $patientIds)->whereIn('status', ['pending', 'held_critical', 'withheld', 'never'])->orderByDesc('reported_at')->get(['title', 'reported_at']);
    }

    public function findReleased(array $patientIds, string $publicId): PortalResultRelease
    {
        return PortalResultRelease::query()->whereIn('patient_id', $patientIds)->where('public_id', $publicId)->where('status', 'released')->firstOrFail();
    }

    /** @return array<string, mixed> */
    public function view(PortalResultRelease $release): array
    {
        abort_unless($release->status === 'released', 404);

        return $release->source_type === 'lab_report' ? $this->lab($release) : $this->radiology($release);
    }

    /** @return array<string, mixed> */
    private function lab(PortalResultRelease $release): array
    {
        $report = LabReport::query()->findOrFail($release->source_id);
        $rows = LabResult::query()->whereIn('lab_order_item_id', DB::table('lab_order_items')->where('lab_order_id', $report->lab_order_id)->select('id'))->where('is_current', true)->orderBy('id')->get()
            ->map(function (LabResult $r): array {
                $test = LabTest::query()->whereKey($r->test_id)->first(['name', 'unit']);

                return ['test' => $test?->name, 'value' => $r->numeric_value !== null ? rtrim(rtrim((string) $r->numeric_value, '0'), '.') : ($r->qualitative_value ?? $r->text_value), 'unit' => $r->unit ?: $test?->unit,
                    'range' => $r->reference_range_text ?: ($r->reference_range_low !== null ? rtrim(rtrim((string) $r->reference_range_low, '0'), '.').' – '.rtrim(rtrim((string) $r->reference_range_high, '0'), '.') : null), 'flag' => $r->abnormal_flag];
            });

        return ['kind' => 'lab', 'title' => $release->title, 'number' => $report->report_number, 'date' => $report->generated_at, 'amended' => $report->status === 'amended', 'rows' => $rows, 'patient_id' => $release->patient_id];
    }

    /** @return array<string, mixed> */
    private function radiology(PortalResultRelease $release): array
    {
        $report = RadiologyReport::query()->findOrFail($release->source_id);
        $study = RadiologyStudy::query()->where('radiology_examination_id', $report->examination_id)->first();
        $viewer = (string) config('portal.viewer_url', '');

        return ['kind' => 'radiology', 'title' => $release->title, 'number' => $report->report_number, 'date' => $report->approved_at, 'amended' => $report->status === 'amended', 'technique' => $report->technique, 'findings' => $report->findings,
            'impression' => $report->impression, 'recommendation' => $report->recommendation, 'viewer_url' => $viewer !== '' && $study ? str_replace('{accession}', rawurlencode((string) $study->accession_number), $viewer) : null, 'patient_id' => $release->patient_id];
    }

    /** @return Collection<int, Prescription> */
    public function prescriptions(array $patientIds, int $limit = 20): Collection
    {
        return Prescription::query()->with(['items' => fn ($q) => $q->where('is_active', true), 'doctor:id,name'])->whereIn('patient_id', $patientIds)->whereNotNull('issued_at')->orderByDesc('issued_at')->limit($limit)->get();
    }
}
