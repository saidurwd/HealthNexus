<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Mch\MchNewborn;
use App\Models\Mch\MchPregnancy;
use App\Models\Patient;
use App\Models\Vital\VrBirthNotification;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Mch\ImmunisationService;
use App\Services\Mch\PregnancyService;
use App\Services\Vital\BirthService;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Mothers and babies, six months. Women are booked at different stages: some delivered during the period (their antenatal visits at the planned weeks, one with high blood pressure, one anaemic, one caesarean, one stillbirth,
 * one set of twins), some still being followed (one high-risk, one never seen since booking, one past her date), one closed after a miscarriage. Each live birth opens the baby's record and a draft birth notification (half are then
 * issued by a second person). The babies are vaccinated on their schedule by the immunisation nurse, but a few doses are missed so some children are overdue. Nothing here draws random numbers, so the other modules' history is not disturbed.
 */
class MchHistory implements DemoHistoryModule
{
    public function __construct(private readonly PregnancyService $pregnancies, private readonly ImmunisationService $immunisation, private readonly BirthService $births) {}

    public function name(): string
    {
        return 'Mothers and babies';
    }

    public function module(): string
    {
        return 'mch';
    }

    public function run(HistoryContext $context): string
    {
        $midwife = $context->staff('mch_midwife')->first();
        $nurse = $context->staff('immunisation_nurse')->first();
        $second = $context->staff('records_manager')->first();
        $women = Patient::query()->where('organization_id', $context->organization->id)->whereIn('sex', ['F', 'female', 'Female'])->whereNull('deceased_at')->whereNotIn('id', MchPregnancy::query()->select('patient_id'))->orderBy('id')->limit(14)->get()->values();
        $to = $context->to;
        $span = max(10, (int) $context->from->diffInDays($context->to));     // deliveries are spread over the history, whatever its length
        $n = ['booked' => 0, 'visits' => 0, 'deliveries' => 0, 'doses' => 0, 'issued' => 0];

        foreach ($women as $i => $w) {
            if ($i <= 5) {                      // delivered during the period, a month apart
                $delivered = $context->from->addDays((int) floor($span * (0.12 + $i * 0.13)))->setTime(2 + $i * 3, 20);
                if ($delivered->gt($to->subDays(3))) {
                    continue;
                }
                $lmp = $delivered->subWeeks(39)->startOfDay();
                $p = null;
                $context->at($delivered->subWeeks(22), function () use (&$p, $w, $lmp, $midwife, $i) {
                    $p = $this->pregnancies->book($w, ['lmp' => $lmp->toDateString(), 'gravida' => $i === 3 ? 3 : 1 + ($i % 2), 'para' => $i === 3 ? 2 : ($i % 2), 'risk_factors' => $i === 1 ? ['hypertension'] : ($i === 3 ? ['previous_caesarean'] : [])], $midwife);
                });
                $n['booked']++;
                foreach ([20, 26, 32, 36] as $k => $weeks) {
                    $at = $lmp->addWeeks($weeks)->setTime(10, 30);
                    $context->at($at, function () use ($p, $midwife, $at, $i, $k, &$n) {
                        $hot = $i === 1 && $k >= 2;
                        $this->pregnancies->recordVisit($p->refresh(), ['visited_at' => $at, 'bp_systolic' => $hot ? 152 : 112 + ($i % 4) * 3, 'bp_diastolic' => $hot ? 98 : 72 + ($i % 3) * 2, 'weight_kg' => 56 + $k * 2 + $i, 'fundal_height_cm' => 18 + $k * 4,
                            'fetal_heart_rate' => 138 + ($i + $k) % 8, 'hemoglobin' => $i === 2 ? 9.6 : 11.8 + ($i % 3) * 0.3, 'urine_protein' => $hot ? 'plus2' : 'nil', 'tt_given' => $k === 0, 'next_visit_on' => $at->addWeeks(4)->toDateString()], $midwife);
                        $n['visits']++;
                    });
                }
                $babies = $i === 5 ? [['sex' => 'male', 'outcome' => 'stillbirth', 'weight_g' => 1900]] : ($i === 4 ? [['sex' => 'female', 'weight_g' => 2700, 'apgar_1' => 8, 'apgar_5' => 9], ['sex' => 'male', 'weight_g' => 2400, 'apgar_1' => 7, 'apgar_5' => 9]]
                    : [['sex' => $i % 2 ? 'female' : 'male', 'weight_g' => 2200 + $i * 260, 'apgar_1' => 8, 'apgar_5' => 9, 'breastfed_within_hour' => true, 'vitamin_k_given' => true]]);
                $context->at($delivered, function () use ($p, $midwife, $delivered, $i, $babies, &$n) {
                    $this->pregnancies->deliver($p->refresh(), ['delivered_at' => $delivered, 'mode' => $i === 3 ? 'caesarean' : 'normal', 'attendant_name' => 'Duty obstetrician', 'blood_loss_ml' => $i === 2 ? 900 : 300,
                        'complications' => $i === 2 ? ['postpartum_haemorrhage'] : [], 'mother_outcome' => 'well'], $babies, $midwife);
                    $n['deliveries']++;
                });
            } elseif ($i <= 9) {                // still being followed
                $weeks = [12, 24, 30, 36][$i - 6];
                $lmp = $to->subWeeks($weeks)->startOfDay();
                $p = null;
                $context->at($to->subWeeks(max(1, $weeks - 8)), function () use (&$p, $w, $lmp, $midwife, $i) {
                    $p = $this->pregnancies->book($w, ['lmp' => $lmp->toDateString(), 'gravida' => 2, 'para' => 1, 'risk_factors' => $i === 8 ? ['diabetes', 'age_over_35'] : []], $midwife);
                });
                $n['booked']++;
                if ($i === 9) {
                    continue;                   // booked and never seen again: listed as not seen for 6 weeks
                }
                $at = $to->subDays(5 + $i)->setTime(11, 0);
                $context->at($at, function () use ($p, $midwife, $at, $i, &$n) {
                    $this->pregnancies->recordVisit($p->refresh(), ['visited_at' => $at, 'bp_systolic' => $i === 8 ? 146 : 114, 'bp_diastolic' => $i === 8 ? 92 : 74, 'weight_kg' => 62, 'hemoglobin' => 11.5, 'urine_protein' => 'nil'], $midwife);
                    $n['visits']++;
                });
            } elseif ($i === 10) {              // past her date, last seen long ago
                $lmp = $to->subWeeks(42)->startOfDay();
                $context->at($to->subWeeks(30), fn () => $this->pregnancies->book($w, ['lmp' => $lmp->toDateString(), 'gravida' => 3, 'para' => 2], $midwife));
                $n['booked']++;
            } elseif ($i === 11) {              // lost early
                $p = null;
                $context->at($to->subWeeks(14), function () use (&$p, $w, $to, $midwife) {
                    $p = $this->pregnancies->book($w, ['lmp' => $to->subWeeks(20)->toDateString(), 'gravida' => 1, 'para' => 0], $midwife);
                });
                $context->at($to->subWeeks(10), fn () => $this->pregnancies->close($p->refresh(), 'miscarriage', $midwife));
                $n['booked']++;
            }
        }

        // the babies are vaccinated on schedule, with a few doses missed
        foreach (MchNewborn::query()->where('organization_id', $context->organization->id)->where('outcome', 'live_birth')->whereNotNull('child_patient_id')->with('delivery')->orderBy('id')->get()->values() as $j => $baby) {
            $child = Patient::query()->find($baby->child_patient_id);
            $dob = CarbonImmutable::parse($baby->delivery->delivered_at);
            foreach ($this->immunisation->schedule() as $vaccine => [$name, $days]) {
                $due = $dob->addDays((int) $days)->setTime(11, 0);
                $missed = ($j % 3 === 2 && $days >= 42) || ($j === 0 && $days >= 98);
                if (! $child || $due->gt($to->subDay()) || $missed) {
                    continue;
                }
                $context->at($due->addDays($days === 0 ? 0 : $j % 4), function () use ($child, $vaccine, $nurse, $due, $j, &$n) {
                    $this->immunisation->give($child, $vaccine, ['given_at' => $due, 'batch_no' => 'EPI-'.$due->format('ym').'-'.(11 + $j), 'site' => str_starts_with($vaccine, 'bcg') ? 'left upper arm' : null], $nurse);
                    $n['doses']++;
                });
            }
        }

        // a second person checks and issues half of the birth notifications this opened
        foreach (VrBirthNotification::query()->where('organization_id', $context->organization->id)->where('status', 'draft')->whereIn('id', MchNewborn::query()->whereNotNull('birth_notification_id')->select('birth_notification_id'))->orderBy('id')->get()->values() as $k => $b) {
            if ($k % 2 === 0) {
                try {
                    $context->at(CarbonImmutable::parse($b->born_at)->addDay(), fn () => $this->births->issue($b->refresh(), $second, 'Father of the child'));
                    $n['issued']++;
                } catch (Throwable) {
                    // the same person wrote and would check it: left as a draft
                }
            }
        }

        return sprintf('%d pregnancies booked, %d antenatal visits, %d deliveries, %d vaccine doses, %d birth notifications issued', $n['booked'], $n['visits'], $n['deliveries'], $n['doses'], $n['issued']);
    }
}
