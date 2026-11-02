<?php

namespace App\Services\Ai;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place the AI reads clinical records, and it only reads. It builds the facts for ONE patient (or one visit) the signed-in person is allowed to see, in their own hospital, with the minimum needed:
 * no names, phone numbers or addresses go in (the facts carry dates, codes and values), every fact has a reference like `lab:12`, and free text written by people is marked untrusted. Facts of another patient cannot
 * get in: each is checked against the patient the request is about.
 */
class ContextEngine
{
    public function __construct(private readonly AiPolicy $policy) {}

    /** @return list<array{ref:string,text:string,section:string,kind:string,date:?string,untrusted?:bool,patient_id:int}> */
    public function timeline(User $user, Patient $patient, ?int $days = null): array
    {
        $this->authorise($user, $patient);
        $since = CarbonImmutable::now()->subDays($days ?? $this->policy->int('context.timeline_days', 365));
        $org = (int) $patient->organization_id;
        $items = array_merge($this->allergies($patient), $this->diagnoses($patient, $since), $this->admissions($patient, $since), $this->visits($patient, $since), $this->vitals($patient, $since, null), $this->results($patient, $since, null), $this->prescriptions($patient, $since));

        return $this->finish($items, $patient, $org);
    }

    /** Everything about one visit, for a note draft. @return list<array<string,mixed>> */
    public function visit(User $user, Encounter $encounter): array
    {
        $patient = Patient::query()->findOrFail($encounter->patient_id);
        $this->authorise($user, $patient);
        if ($encounter->organization_id !== $patient->organization_id) {
            throw ValidationException::withMessages(['encounter' => __('That visit is not this hospital\'s.')]);
        }
        $items = array_merge($this->allergies($patient), $this->diagnoses($patient, CarbonImmutable::now()->subYears(5)), $this->vitals($patient, CarbonImmutable::now()->subYears(5), (int) $encounter->id), $this->results($patient, CarbonImmutable::now()->subYears(5), (int) $encounter->id));
        $items[] = ['ref' => 'enc:'.$encounter->id, 'text' => __('Visit :n (:t) started :d, status :s.', ['n' => $encounter->encounter_no, 't' => str_replace('_', ' ', (string) $encounter->encounter_type), 'd' => $this->d($encounter->started_at), 's' => $encounter->status]),
            'section' => __('Visit'), 'kind' => 'visit', 'date' => $encounter->started_at?->toDateString(), 'patient_id' => (int) $patient->id];
        foreach (DB::table('encounter_notes')->where('encounter_id', $encounter->id)->orderBy('id')->limit(10)->get(['id', 'section', 'content']) as $n) {
            $items[] = ['ref' => 'note:'.$n->id, 'text' => __('Note (:s): :c', ['s' => $n->section ?: __('general'), 'c' => $n->content]), 'section' => __('Notes already written'), 'kind' => 'note', 'date' => null, 'untrusted' => true, 'patient_id' => (int) $patient->id];
        }
        foreach ([__('Presenting complaint and history'), __('Examination'), __('Assessment'), __('Plan')] as $k => $label) {
            $items[] = ['ref' => 'ph:'.($k + 1), 'text' => __(':l — for the clinician to write.', ['l' => $label]), 'section' => __('To be completed by the clinician'), 'kind' => 'placeholder', 'placeholder' => true, 'date' => null, 'patient_id' => (int) $patient->id];
        }

        return $this->finish($items, $patient, (int) $patient->organization_id);
    }

    /** The person must hold the AI right and the right to see patients, and the patient must be in their hospital. */
    public function authorise(User $user, Patient $patient): void
    {
        $ok = $user->can('ai.use') && $user->can('patients.view') && $user->organizations()->where('organizations.id', $patient->organization_id)->exists();
        if (! $ok) {
            abort(403, __('You are not allowed to use AI on this patient.'));
        }
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function finish(array $items, Patient $patient, int $org): array
    {
        foreach ($items as $i) {
            if (($i['patient_id'] ?? null) !== (int) $patient->id) {
                throw new \LogicException('A fact of another patient reached the context.'); // wrong-patient protection
            }
        }
        usort($items, fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        $items = array_slice($items, 0, $this->policy->int('context.max_items', 80));

        return array_map(function ($i) {
            unset($i['date']);

            return $i;
        }, $items);
    }

    private function d(mixed $date): string
    {
        return $date ? CarbonImmutable::parse((string) $date)->setTimezone((string) config('ai.timezone', 'Asia/Dhaka'))->format('d M Y') : '—';
    }

    /** @return list<array<string,mixed>> */
    private function allergies(Patient $p): array
    {
        return DB::table('patient_allergies')->where('patient_id', $p->id)->where('is_active', true)->whereNull('deleted_at')->get(['id', 'substance', 'severity', 'reaction'])->map(fn ($a) => ['ref' => 'allergy:'.$a->id,
            'text' => __('Allergy: :s:d', ['s' => $a->substance, 'd' => ($a->severity || $a->reaction) ? ' ('.trim(($a->severity ?? '').' '.($a->reaction ?? '')).')' : '']), 'section' => __('Allergies'), 'kind' => 'allergy', 'date' => '9999-12-31', 'patient_id' => (int) $p->id])->all();
    }

    /** @return list<array<string,mixed>> */
    private function diagnoses(Patient $p, CarbonImmutable $since): array
    {
        return DB::table('diagnoses')->where('patient_id', $p->id)->whereNull('deleted_at')->where(fn ($q) => $q->whereBetween('recorded_at', [$since, now()])->orWhereNull('recorded_at'))->orderByDesc('recorded_at')->limit(30)
            ->get(['id', 'code', 'description', 'status', 'recorded_at'])->map(fn ($d) => ['ref' => 'dx:'.$d->id, 'text' => __('Diagnosis (:s): :d:c, recorded :r.', ['s' => $d->status, 'd' => $d->description, 'c' => $d->code ? ' ['.$d->code.']' : '', 'r' => $this->d($d->recorded_at)]),
                'section' => __('Diagnoses'), 'kind' => 'diagnosis', 'date' => $d->recorded_at ?? '9999-01-01', 'patient_id' => (int) $p->id])->all();
    }

    /** @return list<array<string,mixed>> */
    private function admissions(Patient $p, CarbonImmutable $since): array
    {
        return DB::table('ipd_admissions')->where('patient_id', $p->id)->whereBetween('admitted_at', [$since, now()])->orderByDesc('admitted_at')->limit(15)->get(['id', 'admission_number', 'admitted_at', 'actual_discharge_date', 'status'])
            ->map(fn ($a) => ['ref' => 'adm:'.$a->id, 'text' => __('Admission :n from :a:d, status :s.', ['n' => $a->admission_number, 'a' => $this->d($a->admitted_at), 'd' => $a->actual_discharge_date ? __(' to :x', ['x' => $this->d($a->actual_discharge_date)]) : '', 's' => $a->status]),
                'section' => __('Admissions'), 'kind' => 'admission', 'date' => $a->admitted_at, 'patient_id' => (int) $p->id])->all();
    }

    /** @return list<array<string,mixed>> */
    private function visits(Patient $p, CarbonImmutable $since): array
    {
        $out = [];
        foreach (DB::table('encounters')->where('patient_id', $p->id)->whereNull('deleted_at')->whereBetween('started_at', [$since, now()])->orderByDesc('started_at')->limit(25)->get(['id', 'encounter_no', 'encounter_type', 'started_at', 'status', 'notes']) as $e) {
            $out[] = ['ref' => 'enc:'.$e->id, 'text' => __('Visit :n (:t) on :d, status :s.', ['n' => $e->encounter_no, 't' => str_replace('_', ' ', (string) $e->encounter_type), 'd' => $this->d($e->started_at), 's' => $e->status]), 'section' => __('Visits'), 'kind' => 'visit', 'date' => $e->started_at, 'patient_id' => (int) $p->id];
            if (trim((string) $e->notes) !== '') {
                $out[] = ['ref' => 'encnote:'.$e->id, 'text' => __('Visit note of :d: :n', ['d' => $this->d($e->started_at), 'n' => $e->notes]), 'section' => __('Notes'), 'kind' => 'note', 'date' => $e->started_at, 'untrusted' => true, 'patient_id' => (int) $p->id];
            }
        }

        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function vitals(Patient $p, CarbonImmutable $since, ?int $encounterId): array
    {
        return DB::table('encounter_vitals')->where('patient_id', $p->id)->when($encounterId, fn ($q) => $q->where('encounter_id', $encounterId))->whereBetween('recorded_at', [$since, now()])->orderByDesc('recorded_at')->limit($encounterId ? 5 : 10)
            ->get()->map(function ($v) use ($p) {
                $bits = array_filter([$v->systolic ? 'BP '.$v->systolic.'/'.$v->diastolic : null, $v->pulse_rate ? 'pulse '.$v->pulse_rate : null, $v->temperature ? 'temperature '.(float) $v->temperature : null, $v->oxygen_saturation ? 'SpO2 '.$v->oxygen_saturation.'%' : null,
                    $v->respiratory_rate ? 'respiratory rate '.$v->respiratory_rate : null, $v->weight ? 'weight '.(float) $v->weight.' kg' : null]);

                return $bits ? ['ref' => 'vit:'.$v->id, 'text' => __('Vitals on :d: :b.', ['d' => $this->d($v->recorded_at), 'b' => implode(', ', $bits)]), 'section' => __('Vital signs'), 'kind' => 'vitals', 'date' => $v->recorded_at, 'patient_id' => (int) $p->id] : null;
            })->filter()->values()->all();
    }

    /** Abnormal and critical reported results. @return list<array<string,mixed>> */
    private function results(Patient $p, CarbonImmutable $since, ?int $encounterId): array
    {
        return DB::table('lab_results as r')->join('lab_order_items as i', 'i.id', '=', 'r.lab_order_item_id')->join('lab_orders as o', 'o.id', '=', 'i.lab_order_id')->leftJoin('lab_tests as t', 't.id', '=', 'r.test_id')
            ->where('o.patient_id', $p->id)->when($encounterId, fn ($q) => $q->where('o.encounter_id', $encounterId))->whereNotNull('r.reported_at')->whereBetween('r.reported_at', [$since, now()])->where(fn ($q) => $q->whereNotNull('r.abnormal_flag')->orWhere('r.critical_flag', true))
            ->orderByDesc('r.reported_at')->limit(20)->get(['r.id', 't.name as test', 'i.requested_test_name', 'r.numeric_value', 'r.text_value', 'r.qualitative_value', 'r.unit', 'r.abnormal_flag', 'r.critical_flag', 'r.reference_range_low', 'r.reference_range_high', 'r.reported_at'])
            ->map(function ($r) use ($p) {
                $value = $r->numeric_value !== null ? (float) $r->numeric_value.($r->unit ? ' '.$r->unit : '') : ($r->qualitative_value ?: ($r->text_value ?: '—'));
                $range = $r->reference_range_low !== null && $r->reference_range_high !== null ? ', reference '.(float) $r->reference_range_low.'–'.(float) $r->reference_range_high : '';

                return ['ref' => 'lab:'.$r->id, 'text' => __('Result on :d: :t :v (:f):r.', ['d' => $this->d($r->reported_at), 't' => $r->test ?: $r->requested_test_name, 'v' => $value, 'f' => $r->critical_flag ? 'critical' : $r->abnormal_flag, 'r' => $range]),
                    'section' => __('Abnormal results'), 'kind' => 'lab', 'date' => $r->reported_at, 'patient_id' => (int) $p->id];
            })->all();
    }

    /** @return list<array<string,mixed>> */
    private function prescriptions(Patient $p, CarbonImmutable $since): array
    {
        $out = [];
        foreach (DB::table('prescriptions')->where('patient_id', $p->id)->whereNull('deleted_at')->whereBetween('prescribed_at', [$since, now()])->orderByDesc('prescribed_at')->limit(10)->get(['id', 'prescription_no', 'prescribed_at', 'advice']) as $r) {
            $out[] = ['ref' => 'rx:'.$r->id, 'text' => __('Prescription :n on :d.', ['n' => $r->prescription_no, 'd' => $this->d($r->prescribed_at)]), 'section' => __('Prescriptions'), 'kind' => 'prescription', 'date' => $r->prescribed_at, 'patient_id' => (int) $p->id];
            if (trim((string) $r->advice) !== '') {
                $out[] = ['ref' => 'rxadvice:'.$r->id, 'text' => __('Advice on :d: :a', ['d' => $this->d($r->prescribed_at), 'a' => $r->advice]), 'section' => __('Notes'), 'kind' => 'note', 'date' => $r->prescribed_at, 'untrusted' => true, 'patient_id' => (int) $p->id];
            }
        }

        return $out;
    }
}
