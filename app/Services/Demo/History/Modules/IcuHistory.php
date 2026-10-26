<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuVentilatorEvent;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Provider;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Icu\IcuAdmissionRequestService;
use App\Services\Icu\IcuAssessmentService;
use App\Services\Icu\IcuChargeService;
use App\Services\Icu\IcuDischargeService;
use App\Services\Icu\IcuEpisodeService;
use App\Services\Icu\IcuFluidBalanceService;
use App\Services\Icu\IcuInfusionService;
use App\Services\Icu\IcuObservationService;
use App\Services\Icu\IcuProgressNoteService;
use App\Services\Icu\IcuScoringService;
use App\Services\Icu\IcuVentilatorService;
use App\Services\Ipd\IpdBedStatusService;
use App\Services\Ipd\IpdDischargeService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Critical-care stays day by day: admission into a free ICU bed, four-hourly observations, scores, ventilation, infusions, fluid balance, notes, daily charges and discharge. */
class IcuHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly IcuAdmissionRequestService $requests,
        private readonly IcuEpisodeService $episodes,
        private readonly IcuAssessmentService $assessments,
        private readonly IcuObservationService $observations,
        private readonly IcuScoringService $scoring,
        private readonly IcuVentilatorService $ventilator,
        private readonly IcuInfusionService $infusions,
        private readonly IcuFluidBalanceService $fluids,
        private readonly IcuProgressNoteService $notes,
        private readonly IcuChargeService $charges,
        private readonly IcuDischargeService $discharge,
        private readonly IpdDischargeService $ipdDischarge,
        private readonly IpdBedStatusService $bedStatus,
    ) {}

    public function name(): string
    {
        return 'ICU';
    }

    public function module(): string
    {
        return 'icu';
    }

    public function run(HistoryContext $context): string
    {
        $encounters = collect($context->shared('encounters'));
        if ($encounters->isEmpty()) {
            return 'skipped: no outpatient encounters to refer from';
        }
        // A bed left in 'cleaning' by an earlier module's last movement is cleaned before the unit opens its books.
        IpdBed::where('organization_id', $context->organization->id)->where('icu_capable', true)->where('status', IpdBed::STATUS_CLEANING)->get()
            ->each(fn (IpdBed $b) => $this->bedStatus->transitionTo($b, IpdBed::STATUS_AVAILABLE, $context->actor, 'Cleaned'));
        $physician = $context->staff('icu_physician')->first();
        $nurse = $context->staff('charge_nurse')->first();
        $coordinator = $context->staff('ipd_coordinator')->first();
        $provider = Provider::firstOrCreate(['organization_id' => $context->organization->id, 'user_id' => $physician->id, 'provider_type' => 'doctor'], ['branch_id' => $context->branch->id, 'provider_code' => 'ICUD-'.$physician->id, 'name' => $physician->name, 'status' => 'active']);
        $perWeek = max(1, intdiv($context->visitsPerDay, 3));
        $active = [];
        $counts = ['admitted' => 0, 'discharged' => 0, 'ventilated' => 0, 'no_bed' => 0];

        foreach ($context->days() as $day) {
            foreach ($active as $id => $due) {
                if ($due->lte($day)) {
                    $this->release($context, IcuEpisode::findOrFail($id), $day, $physician, $coordinator);
                    unset($active[$id]);
                    $counts['discharged']++;
                }
            }

            if ($day->dayOfWeek === 0 || $context->chance(100 - min(90, $perWeek * 12))) {
                $episode = $this->admit($context, $day, $encounters, $physician, $provider);
                if ($episode === false) {
                    $counts['no_bed']++;
                } elseif ($episode) {
                    $counts['admitted']++;
                    $ventilated = $context->chance(45);
                    $ventilated && $counts['ventilated']++;
                    $active[$episode->id] = $day->addDays(mt_rand(3, 9));
                    $this->firstDay($context, $episode, $day, $physician, $nurse, $ventilated);
                }
            }

            foreach (array_keys($active) as $id) {
                $this->routineDay($context, IcuEpisode::findOrFail($id), $day, $nurse, $physician);
            }
        }

        return sprintf('%d ICU stays (%d ventilated, %d discharged, %d still inside), %d refused for lack of a bed', $counts['admitted'], $counts['ventilated'], $counts['discharged'], count($active), $counts['no_bed']);
    }

    /** @return IcuEpisode|false|null false = no bed, null = nothing done */
    private function admit(HistoryContext $context, CarbonImmutable $day, $encounters, $physician, Provider $provider): IcuEpisode|false|null
    {
        $bed = IpdBed::where('organization_id', $context->organization->id)->where('icu_capable', true)->where('status', IpdBed::STATUS_AVAILABLE)->where('is_active', true)->first();
        if (! $bed) {
            return false;
        }
        $case = $context->pick($encounters);
        if (IpdAdmission::where('patient_id', $case['patient_id'])->whereNotIn('status', ['discharged', 'closed', 'cancelled'])->exists()) {
            return null;
        }
        $at = $context->workTime($day);
        try {
            return $context->at($at, function () use ($context, $case, $physician, $provider, $bed) {
                $request = $this->requests->create([
                    'organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'patient_id' => $case['patient_id'], 'encounter_id' => $case['id'],
                    'requesting_provider_id' => $provider->id, 'admission_reason' => $context->pick(['Respiratory failure', 'Septic shock', 'Post-operative monitoring', 'Diabetic ketoacidosis', 'Acute heart failure']),
                    'priority' => 'urgent', 'is_emergency' => true, 'override_reason' => 'Immediate critical-care need',
                ], $physician);

                return $this->episodes->admit($request->refresh(), ['bed_id' => $bed->id, 'attending_provider_id' => $provider->id], $physician);
            });
        } catch (ValidationException) {
            return null;
        }
    }

    private function firstDay(HistoryContext $context, IcuEpisode $episode, CarbonImmutable $day, $physician, $nurse, bool $ventilated): void
    {
        $at = CarbonImmutable::parse($episode->admitted_at);
        $context->at($at->addMinutes(20), function () use ($episode, $physician, $ventilated) {
            $assessment = $this->assessments->create($episode, ['sections' => ['general' => 'Critically ill, monitored', 'airway' => $ventilated ? 'Intubated' : 'Patent']], $physician);
            $this->assessments->finalize($assessment, $physician);
            if ($ventilated) {
                $this->ventilator->recordEvent($episode, ['event_type' => IcuVentilatorEvent::TYPE_INITIATED, 'mode' => 'SIMV', 'fio2' => 60, 'peep' => 8, 'tidal_volume' => 450, 'respiratory_rate' => 16], $physician);
                $this->infusions->start($episode, ['medication' => 'Noradrenaline', 'concentration' => '4 mg/50 ml', 'initial_rate' => 5, 'rate_unit' => 'ml/h', 'route' => 'IV', 'prescriber_id' => null], $physician);
            }
        });
    }

    private function routineDay(HistoryContext $context, IcuEpisode $episode, CarbonImmutable $day, $nurse, $physician): void
    {
        $from = CarbonImmutable::parse($episode->admitted_at);
        foreach ([2, 6, 10, 14, 18, 22] as $hour) {
            $at = $day->setTime($hour, 0);
            if ($at->lt($from->addMinutes(30))) {
                continue;
            }
            $context->at($at, function () use ($episode, $nurse, $hour) {
                $this->observations->record($episode, ['observation_type' => 'heart_rate', 'value_numeric' => mt_rand(70, 125), 'unit' => 'bpm'], $nurse);
                $this->observations->record($episode, ['observation_type' => 'spo2', 'value_numeric' => mt_rand(91, 99), 'unit' => '%'], $nurse);
                $this->observations->record($episode, ['observation_type' => 'systolic_bp', 'value_numeric' => mt_rand(92, 150), 'unit' => 'mmHg'], $nurse);
                $this->observations->record($episode, ['observation_type' => 'respiratory_rate', 'value_numeric' => mt_rand(12, 26), 'unit' => '/min'], $nurse);
                $this->fluids->recordEntry($episode, ['type' => 'intake', 'category' => 'iv', 'volume_ml' => mt_rand(80, 250)], $nurse);
                $this->fluids->recordEntry($episode, ['type' => 'output', 'category' => 'urine', 'volume_ml' => mt_rand(60, 220)], $nurse);
                if ($hour === 6) {
                    try {
                        $this->scoring->calculate($episode, 'gcs', ['eye_opening' => mt_rand(2, 4), 'verbal_response' => mt_rand(2, 5), 'motor_response' => mt_rand(3, 6)], $nurse);
                    } catch (ValidationException) {
                        // the hospital has not configured the score (settings not seeded): the rest of the stay is still recorded
                    }
                }
            });
        }
        $context->at($day->setTime(9, 0), function () use ($episode, $physician) {
            $note = $this->notes->create($episode, ['note_type' => 'daily', 'subjective' => 'No new complaints.', 'objective' => 'Haemodynamically stable on current support.', 'assessment' => 'Improving.', 'plan' => 'Continue current management, review in the morning.'], $physician);
            $this->notes->finalize($note, $physician);
        });
        $context->at($day->setTime(23, 30), function () use ($episode, $physician, $day) {
            $this->charges->chargeBedDay($episode, $day->toDateString(), $physician);
            if ($episode->ventilatorEvents()->exists()) {
                $this->charges->chargeVentilatorDay($episode, $day->toDateString(), $physician);
            }
            $this->charges->chargeCriticalCareService($episode, $day->toDateString(), $physician);
        });
    }

    private function release(HistoryContext $context, IcuEpisode $episode, CarbonImmutable $day, $physician, $coordinator): void
    {
        $at = $day->setTime(12, 0);
        $bed = IpdBed::find($episode->icu_bed_id);
        $summary = $context->at($at, fn () => $this->discharge->start($episode, ['destination' => $context->pick(['ward', 'discharge', 'hdu']), 'current_status' => 'Improved and stable for transfer'], $physician));
        $context->at($at->addMinutes(30), fn () => $this->discharge->finalize($summary, $physician));
        if ($bed && $bed->refresh()->status === IpdBed::STATUS_CLEANING) {
            $context->at($at->addHours(2), fn () => $this->bedStatus->transitionTo($bed, IpdBed::STATUS_AVAILABLE, $context->actor, 'Cleaned'));
        }

        // The IPD admission the ICU stay opened is closed the next day, so nobody stays "admitted" for months.
        $admission = IpdAdmission::find($episode->admission_id);
        if ($admission && in_array($admission->status, ['admitted', 'active'], true)) {
            $context->at($at->addDay(), function () use ($admission, $coordinator) {
                $request = $this->ipdDischarge->request($admission, ['discharge_type' => 'routine'], $coordinator);
                foreach (['clinical', 'billing', 'pharmacy'] as $type) {
                    $request = $this->ipdDischarge->recordClearance($request->refresh(), $type, $coordinator);
                }
                $this->ipdDischarge->complete($request->refresh(), $coordinator);
            });
        }
    }
}
