<?php

namespace App\Services\Mch;

use App\Models\Mch\MchAncVisit;
use App\Models\Mch\MchDelivery;
use App\Models\Mch\MchNewborn;
use App\Models\Mch\MchPregnancy;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use App\Services\Numbering\NumberSeriesService;
use App\Services\Patients\PatientService;
use App\Services\Vital\BirthService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A woman's pregnancy: booked with the expected date of delivery, followed by antenatal visits whose readings raise alerts, ended by a delivery (with the newborns) or by another outcome. A live birth can open the baby's own
 * patient record and, where the births module is on, a draft birth notification for a second person to check and issue - so the facts are written once.
 */
class PregnancyService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly MchPolicy $policy, private readonly PatientService $patients, private readonly BirthService $births, private readonly ModuleManager $modules, private readonly AuditLogger $audit) {}

    /** The expected date of delivery by Naegele's rule: the last period plus 280 days. */
    public static function eddFrom(string $lmp): CarbonImmutable
    {
        return CarbonImmutable::parse($lmp)->addDays(280);
    }

    /** @param  array<string,mixed>  $d  lmp or edd, gravida, para, risk_factors */
    public function book(Patient $patient, array $d, User $by): MchPregnancy
    {
        if (in_array(strtolower((string) $patient->sex), ['m', 'male'], true)) {
            throw ValidationException::withMessages(['patient_id' => __('The patient must be a woman.')]);
        }
        if (MchPregnancy::query()->where(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'status' => 'active'])->exists()) {
            throw ValidationException::withMessages(['patient_id' => __('This woman already has a pregnancy being followed. Close it first.')]);
        }
        $lmp = ! empty($d['lmp']) ? CarbonImmutable::parse((string) $d['lmp']) : null;
        if ($lmp !== null && ($lmp->isFuture() || $lmp->lt(now()->subDays(320)))) {
            throw ValidationException::withMessages(['lmp' => __('The last period must be in the past, within the last 320 days.')]);
        }
        $edd = ! empty($d['edd']) ? CarbonImmutable::parse((string) $d['edd']) : $lmp?->addDays(280);
        if ($edd === null) {
            throw ValidationException::withMessages(['edd' => __('Give the last period or the expected date of delivery.')]);
        }
        $gravida = max(1, (int) ($d['gravida'] ?? 1));
        $para = max(0, (int) ($d['para'] ?? 0));
        if ($para >= $gravida) {
            throw ValidationException::withMessages(['para' => __('Earlier births must be fewer than the number of pregnancies, this one included.')]);
        }
        $risks = array_values(array_intersect((array) ($d['risk_factors'] ?? []), $this->policy->list('risk_factors')));

        return DB::transaction(function () use ($patient, $by, $lmp, $edd, $gravida, $para, $risks, $d) {
            $p = MchPregnancy::create([
                'organization_id' => $patient->organization_id, 'branch_id' => $d['branch_id'] ?? null, 'patient_id' => $patient->id, 'lmp' => $lmp, 'edd' => $edd, 'gravida' => $gravida, 'para' => $para, 'risk_factors' => $risks,
                'status' => 'active', 'booked_at' => now(), 'booked_by' => $by->id,
                'number' => $this->numbers->next('mch.pregnancy', (int) $patient->organization_id, prefix: 'PR', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id),
            ]);
            $this->audit->log('CREATE', MchPregnancy::class, $p->id, null, ['number' => $p->number, 'edd' => $edd->toDateString()]);

            return $p;
        });
    }

    /** Weeks of pregnancy on a day, counted from the expected date of delivery (40 weeks before it). */
    public function weeksOn(MchPregnancy $p, ?CarbonImmutable $day = null): int
    {
        $day ??= CarbonImmutable::now();

        return (int) max(0, min(44, 40 - (int) floor($day->startOfDay()->diffInDays($p->edd, false) / 7)));
    }

    /** @return list<string> alert keys worked out from a visit's readings */
    public function alertsFor(array $d): array
    {
        $t = $this->policy->get('alerts', []);
        $a = [];
        $sys = (int) ($d['bp_systolic'] ?? 0);
        $dia = (int) ($d['bp_diastolic'] ?? 0);
        if ($sys >= $t['bp_systolic_severe'] || $dia >= $t['bp_diastolic_severe']) {
            $a[] = 'bp_severe';
        } elseif ($sys >= $t['bp_systolic_high'] || $dia >= $t['bp_diastolic_high']) {
            $a[] = 'bp_high';
        }
        if (($d['hemoglobin'] ?? '') !== '') {
            $hb = (float) $d['hemoglobin'];
            $hb < $t['hemoglobin_severe'] ? $a[] = 'anaemia_severe' : ($hb < $t['hemoglobin_low'] ? $a[] = 'anaemia' : null);
        }
        if (! empty($d['fetal_heart_rate']) && ((int) $d['fetal_heart_rate'] < $t['fhr_low'] || (int) $d['fetal_heart_rate'] > $t['fhr_high'])) {
            $a[] = 'fetal_heart_rate';
        }
        if (in_array($d['urine_protein'] ?? '', ['plus2', 'plus3'], true) && ($sys >= $t['bp_systolic_high'] || $dia >= $t['bp_diastolic_high'])) {
            $a[] = 'pre_eclampsia';
        }
        if (! empty($d['danger_signs'])) {
            $a[] = 'danger_sign';
        }

        return $a;
    }

    /** @param  array<string,mixed>  $d */
    public function recordVisit(MchPregnancy $p, array $d, User $by): MchAncVisit
    {
        if ($p->status !== 'active') {
            throw ValidationException::withMessages(['status' => __('This pregnancy is closed.')]);
        }
        $at = ! empty($d['visited_at']) ? CarbonImmutable::parse((string) $d['visited_at']) : CarbonImmutable::now();
        if ($at->isFuture() || $at->lt($p->booked_at->subDay())) {
            throw ValidationException::withMessages(['visited_at' => __('The visit cannot be in the future or before the booking.')]);
        }
        $signs = array_values(array_intersect((array) ($d['danger_signs'] ?? []), $this->policy->list('danger_signs')));
        if (($d['urine_protein'] ?? '') !== '' && ! in_array($d['urine_protein'] ?? null, $this->policy->list('urine_protein'), true)) {
            throw ValidationException::withMessages(['urine_protein' => __('Choose the urine protein result.')]);
        }
        $d['danger_signs'] = $signs;

        return DB::transaction(function () use ($p, $d, $by, $at, $signs) {
            $p = MchPregnancy::query()->lockForUpdate()->findOrFail($p->id);
            $n = (int) MchAncVisit::query()->where('pregnancy_id', $p->id)->max('visit_no') + 1;
            $v = MchAncVisit::create([
                'organization_id' => $p->organization_id, 'pregnancy_id' => $p->id, 'visit_no' => $n, 'visited_at' => $at, 'weeks' => $this->weeksOn($p, $at),
                'bp_systolic' => $d['bp_systolic'] ?? null, 'bp_diastolic' => $d['bp_diastolic'] ?? null, 'weight_kg' => $d['weight_kg'] ?? null, 'fundal_height_cm' => $d['fundal_height_cm'] ?? null,
                'fetal_heart_rate' => $d['fetal_heart_rate'] ?? null, 'hemoglobin' => ($d['hemoglobin'] ?? '') !== '' ? $d['hemoglobin'] : null, 'urine_protein' => ($d['urine_protein'] ?? '') !== '' ? $d['urine_protein'] : null,
                'tt_given' => (bool) ($d['tt_given'] ?? false), 'danger_signs' => $signs, 'alerts' => $this->alertsFor($d), 'next_visit_on' => ! empty($d['next_visit_on']) ? $d['next_visit_on'] : null,
                'notes' => isset($d['notes']) ? mb_substr((string) $d['notes'], 0, 500) : null, 'recorded_by' => $by->id,
            ]);
            $this->audit->log('CREATE', MchAncVisit::class, $v->id, null, ['pregnancy' => $p->number, 'visit_no' => $n, 'alerts' => $v->alerts]);

            return $v;
        });
    }

    /**
     * The delivery ends the pregnancy. Each baby is given as ['sex', 'outcome', 'weight_g', 'apgar_1', 'apgar_5', 'breastfed_within_hour', 'vitamin_k_given', 'admitted_to', 'name'].
     *
     * @param  array<string,mixed>  $d
     * @param  list<array<string,mixed>>  $babies
     */
    public function deliver(MchPregnancy $p, array $d, array $babies, User $by, bool $openRecords = true): MchDelivery
    {
        $at = ! empty($d['delivered_at']) ? CarbonImmutable::parse((string) $d['delivered_at']) : CarbonImmutable::now();
        if ($p->status !== 'active') {
            throw ValidationException::withMessages(['status' => __('This pregnancy is closed.')]);
        }
        if ($at->isFuture() || $at->lt($p->booked_at->subDays(2))) {
            throw ValidationException::withMessages(['delivered_at' => __('The delivery cannot be in the future or before the booking.')]);
        }
        if (! in_array($d['mode'] ?? '', $this->policy->list('delivery_modes'), true)) {
            throw ValidationException::withMessages(['mode' => __('Choose how the baby was delivered.')]);
        }
        if ($babies === [] || count($babies) > 4) {
            throw ValidationException::withMessages(['babies' => __('Give one to four babies.')]);
        }
        foreach ($babies as $b) {
            if (! in_array($b['outcome'] ?? 'live_birth', $this->policy->list('newborn_outcomes'), true) || ! in_array($b['sex'] ?? '', ['male', 'female', 'other', 'undetermined'], true)) {
                throw ValidationException::withMessages(['babies' => __('Give each baby\'s sex and outcome.')]);
            }
            foreach (['apgar_1', 'apgar_5'] as $k) {
                if (isset($b[$k]) && $b[$k] !== '' && ((int) $b[$k] < 0 || (int) $b[$k] > 10)) {
                    throw ValidationException::withMessages(['babies' => __('An Apgar score is from 0 to 10.')]);
                }
            }
        }
        $complications = array_values(array_intersect((array) ($d['complications'] ?? []), $this->policy->list('delivery_complications')));

        return DB::transaction(function () use ($p, $d, $babies, $by, $at, $complications, $openRecords) {
            $p = MchPregnancy::query()->lockForUpdate()->findOrFail($p->id);
            $mother = Patient::query()->findOrFail($p->patient_id);
            $delivery = MchDelivery::create([
                'organization_id' => $p->organization_id, 'branch_id' => $p->branch_id, 'pregnancy_id' => $p->id, 'delivered_at' => $at, 'weeks' => $this->weeksOn($p, $at), 'mode' => $d['mode'], 'place' => $d['place'] ?? 'this_hospital',
                'attendant_name' => $d['attendant_name'] ?? null, 'blood_loss_ml' => $d['blood_loss_ml'] ?? null, 'complications' => $complications, 'mother_outcome' => $d['mother_outcome'] ?? 'well', 'recorded_by' => $by->id,
            ]);
            $vital = $openRecords && $this->modules->isEnabled('vital', (int) $p->organization_id);
            foreach ($babies as $i => $b) {
                $outcome = $b['outcome'] ?? 'live_birth';
                $child = null;
                $notification = null;
                if ($outcome === 'live_birth' && $openRecords) {
                    $child = $this->patients->createPatient(['organization_id' => $p->organization_id, 'first_name' => $b['name'] ?? 'Baby', 'last_name' => $mother->last_name, 'date_of_birth' => $at->toDateString(), 'sex' => ['male' => 'M', 'female' => 'F'][$b['sex']] ?? 'O'], $by);
                }
                if ($vital && in_array($outcome, ['live_birth', 'stillbirth'], true)) {
                    $notification = $this->births->draft($mother, ['born_at' => $at, 'sex' => $b['sex'], 'outcome' => $outcome, 'child_name' => $b['name'] ?? null, 'place_of_birth' => $d['place'] ?? 'this_hospital', 'plurality' => count($babies), 'birth_order' => $i + 1,
                        'weight_g' => $b['weight_g'] ?? null, 'gestation_weeks' => $delivery->weeks && $delivery->weeks >= 12 ? $delivery->weeks : null, 'delivery_type' => $d['mode'], 'attendant_name' => $d['attendant_name'] ?? null, 'child_patient_id' => $child?->id, 'branch_id' => $p->branch_id], $by);
                }
                MchNewborn::create([
                    'organization_id' => $p->organization_id, 'delivery_id' => $delivery->id, 'birth_order' => $i + 1, 'outcome' => $outcome, 'sex' => $b['sex'], 'weight_g' => ($b['weight_g'] ?? '') !== '' ? $b['weight_g'] : null,
                    'apgar_1' => ($b['apgar_1'] ?? '') !== '' ? $b['apgar_1'] : null, 'apgar_5' => ($b['apgar_5'] ?? '') !== '' ? $b['apgar_5'] : null, 'breastfed_within_hour' => (bool) ($b['breastfed_within_hour'] ?? false),
                    'vitamin_k_given' => (bool) ($b['vitamin_k_given'] ?? false), 'admitted_to' => ($b['admitted_to'] ?? '') !== '' ? $b['admitted_to'] : null, 'child_patient_id' => $child?->id, 'birth_notification_id' => $notification?->id,
                ]);
            }
            $p->update(['status' => 'delivered', 'closed_at' => $at]);
            $this->audit->log('CREATE', MchDelivery::class, $delivery->id, null, ['pregnancy' => $p->number, 'mode' => $delivery->mode, 'babies' => count($babies)]);

            return $delivery;
        });
    }

    public function close(MchPregnancy $p, string $reason, User $by): MchPregnancy
    {
        if ($p->status !== 'active' || ! in_array($reason, ['miscarriage', 'abortion', 'ectopic', 'transferred', 'lost_to_follow_up'], true)) {
            throw ValidationException::withMessages(['end_reason' => __('Choose why the pregnancy is being closed.')]);
        }
        $p->update(['status' => 'ended', 'end_reason' => $reason, 'closed_at' => now()]);
        $this->audit->log('UPDATE', MchPregnancy::class, $p->id, ['status' => 'active'], ['status' => 'ended', 'end_reason' => $reason]);

        return $p;
    }
}
