<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Ipd\IpdAdmission;
use App\Models\Ot\OtProcedure;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Ot\OtSurgicalSafetyChecklistItem;
use App\Models\Ot\OtTheatre;
use App\Models\Provider;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Ot\OtChargeService;
use App\Services\Ot\OtConsentService;
use App\Services\Ot\OtOperativeNoteService;
use App\Services\Ot\OtPreoperativeAssessmentService;
use App\Services\Ot\OtPreoperativeChecklistService;
use App\Services\Ot\OtReceptionService;
use App\Services\Ot\OtRecoveryService;
use App\Services\Ot\OtSchedulingService;
use App\Services\Ot\OtSpecimenService;
use App\Services\Ot\OtSurgeryService;
use App\Services\Ot\OtSurgicalCountService;
use App\Services\Ot\OtSurgicalSafetyChecklistService;
use App\Services\Ot\OtSurgicalTeamService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Elective and urgent surgery: scheduled into free theatre time, pre-op checks, consent, WHO checklist, counts, operative note, recovery — with some postponements and cancellations. */
class OtHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly OtSurgeryService $surgeries,
        private readonly OtSchedulingService $scheduling,
        private readonly OtReceptionService $reception,
        private readonly OtPreoperativeChecklistService $checklists,
        private readonly OtPreoperativeAssessmentService $assessments,
        private readonly OtConsentService $consent,
        private readonly OtSurgicalTeamService $team,
        private readonly OtSurgicalSafetyChecklistService $safety,
        private readonly OtSurgicalCountService $counts,
        private readonly OtOperativeNoteService $notes,
        private readonly OtSpecimenService $specimens,
        private readonly OtChargeService $charges,
        private readonly OtRecoveryService $recovery,
    ) {}

    public function name(): string
    {
        return 'OT / Surgery';
    }

    public function module(): string
    {
        return 'ot';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $procedures = OtProcedure::where('organization_id', $orgId)->where('is_active', true)->get();
        $theatres = OtTheatre::where('organization_id', $orgId)->where('is_active', true)->get();
        if ($procedures->isEmpty() || $theatres->isEmpty()) {
            return 'skipped: no theatres or procedures in the catalogue';
        }
        $surgeons = $context->staff('surgeon')->map(fn ($u) => $this->provider($context, $u, 'surgeon'));
        $anesthetists = $context->staff('anesthetist')->map(fn ($u) => $this->provider($context, $u, 'anesthetist'));
        $surgeonUser = $context->staff('surgeon')->first();

        $admissions = collect($context->shared('ipd_admissions'));
        $encounters = collect($context->shared('encounters'));
        $busy = [];
        $counts = ['cases' => 0, 'completed' => 0, 'cancelled' => 0, 'postponed' => 0, 'scheduled' => 0];
        $recent = $context->to->subDay()->startOfDay();

        $cases = $admissions->random(min($admissions->count(), max(3, intdiv($admissions->count(), 3))))->map(fn ($a) => $a + ['kind' => 'ward'])
            ->concat($encounters->random(min($encounters->count(), max(3, intdiv($encounters->count(), 12))))->map(fn ($e) => $e + ['kind' => 'day']))
            ->sortBy('at');

        foreach ($cases as $case) {
            $day = CarbonImmutable::parse($case['at'])->addDays($case['kind'] === 'ward' ? 1 : 0)->startOfDay();
            if ($day->gt($context->to)) {
                $day = $context->to;
            }
            $theatre = $context->pick($theatres);
            $key = $theatre->id.'|'.$day->toDateString();
            $start = $day->setTime(8, 0)->addMinutes(($busy[$key] ?? 0) * 150);
            if ($start->hour >= 17) {
                continue;
            }
            if ($case['kind'] === 'ward') {
                // The operation must fall inside the stay; otherwise the patient is operated on as a day case.
                $discharged = IpdAdmission::whereKey($case['id'])->value('actual_discharge_date');
                if ($discharged && CarbonImmutable::parse($discharged)->lt($start->addHours(3))) {
                    continue;
                }
            }
            $busy[$key] = ($busy[$key] ?? 0) + 1;
            $end = $start->addMinutes(120);
            $procedure = $context->pick($procedures);
            $surgeon = $context->pick($surgeons);
            $anesthetist = $context->pick($anesthetists);

            try {
                $surgery = $context->at($start->subDay()->setTime(15, 0), fn () => $this->surgeries->create([
                    'organization_id' => $orgId, 'branch_id' => $context->branch->id, 'patient_id' => $case['patient_id'], 'encounter_id' => $case['encounter_id'] ?? $case['id'],
                    'admission_id' => $case['kind'] === 'ward' ? $case['id'] : null, 'primary_procedure_id' => $procedure->id, 'priority' => $context->chance(20) ? 'urgent' : 'routine',
                    'surgery_type' => $case['kind'] === 'ward' ? 'inpatient' : 'day_case',
                ], $surgeonUser));
                $surgery = $context->at($start->subDay()->setTime(15, 30), fn () => $this->scheduling->schedule($surgery, [
                    'theatre_id' => $theatre->id, 'scheduled_start_at' => $start, 'scheduled_end_at' => $end, 'primary_surgeon_id' => $surgeon->id, 'anesthetist_id' => $anesthetist->id,
                ], $surgeonUser));
            } catch (ValidationException) {
                continue;
            }
            $counts['cases']++;

            if ($context->chance(5)) {
                $context->at($start->subHours(2), fn () => $this->scheduling->postpone($surgery->refresh(), 'Patient not fit — blood pressure high', $surgeonUser));
                $counts['postponed']++;

                continue;
            }
            if ($context->chance(4)) {
                $context->at($start->subHours(2), fn () => $this->scheduling->cancel($surgery->refresh(), 'Patient declined surgery', $surgeonUser));
                $counts['cancelled']++;

                continue;
            }
            if ($start->gte($recent)) {
                $counts['scheduled']++;

                continue;
            }

            $this->perform($context, $surgery, $surgeon, $anesthetist, $surgeonUser, $start, $case['kind']);
            $counts['completed']++;
        }

        return sprintf('%d cases: %d completed, %d still scheduled, %d postponed, %d cancelled', $counts['cases'], $counts['completed'], $counts['scheduled'], $counts['postponed'], $counts['cancelled']);
    }

    private function perform(HistoryContext $context, $surgery, Provider $surgeon, Provider $anesthetist, User $user, CarbonImmutable $start, string $kind): void
    {
        $nurse = $context->staff('ot_nurse')->first();
        $context->at($start->subHours(20), function () use ($surgery, $user, $surgeon, $anesthetist, $context) {
            $this->checklists->initialize($surgery);
            $assessment = $this->assessments->create($surgery, ['sections' => ['airway' => 'clear', 'asa_class' => $context->pick(['I', 'II', 'III']), 'fasting' => 'confirmed']], $user);
            $this->assessments->finalize($assessment, $user);
            $this->consent->grant($surgery, $user, 'Risks and benefits explained', 'Family member', 'bn');
            $this->team->assign($surgery, $surgeon, 'primary_surgeon', $user);
            $this->team->assign($surgery, $anesthetist, 'anesthetist', $user);
        });

        $surgery = $context->at($start->subMinutes(30), fn () => $this->reception->receive($surgery->refresh(), $user));
        $context->at($start->subMinutes(15), function () use ($surgery, $user) {
            $signIn = $this->safety->initializePhase($surgery->refresh(), OtSurgicalSafetyChecklist::PHASE_SIGN_IN);
            foreach (OtSurgicalSafetyChecklistItem::query()->where('checklist_id', $signIn->id)->get() as $item) {
                $this->safety->completeItem($item, $user, 'confirmed');
            }
            $this->safety->completePhase($signIn, $user);
        });
        $surgery = $context->at($start, fn () => $this->reception->start($surgery->refresh(), $user));
        $context->at($start->addMinutes(30), function () use ($surgery, $user, $context) {
            $this->counts->record($surgery, ['count_type' => 'swab', 'stage' => 'initial', 'count_value' => '10'], $user);
            $this->counts->record($surgery, ['count_type' => 'swab', 'stage' => 'final', 'count_value' => '10'], $user);
            $context->chance(25) && $this->specimens->collect($surgery, ['specimen_type' => $context->pick(['appendix', 'tissue', 'gallbladder'])], $user, $context->chance(50));
            $note = $this->notes->create($surgery, ['findings' => 'As expected; haemostasis secure.', 'procedure_performed' => 'Completed as planned.'], $user);
            $this->notes->finalize($note, $user);
        });
        $context->at($start->addMinutes(95), function () use ($surgery, $user) {
            $signOut = $this->safety->initializePhase($surgery->refresh(), OtSurgicalSafetyChecklist::PHASE_SIGN_OUT);
            foreach (OtSurgicalSafetyChecklistItem::query()->where('checklist_id', $signOut->id)->get() as $item) {
                $this->safety->completeItem($item, $user, 'confirmed');
            }
            $this->safety->completePhase($signOut, $user);
        });
        $surgery = $context->at($start->addMinutes(105), fn () => $this->reception->complete($surgery->refresh(), $user));
        $context->at($start->addMinutes(106), function () use ($surgery, $user) {
            $this->charges->chargeProcedureFee($surgery, $user);
            $this->charges->chargeTheatreUsageFee($surgery, $user);
            $this->charges->chargeAnesthesiaFee($surgery, $user);
            $this->charges->chargeSurgeonFee($surgery, $user);
        });

        $context->at($start->addMinutes(110), function () use ($surgery, $user, $kind) {
            $recovery = $this->recovery->admitToRecovery($surgery->refresh());
            $this->recovery->updateAssessment($recovery, ['discharge_criteria_met' => true]);
            $this->recovery->markReadyForTransfer($recovery->refresh());
            // Back to the ward only while the patient is still an inpatient today; anyone already discharged by the IPD history leaves from recovery.
            $stillInside = $kind === 'ward' && in_array(IpdAdmission::whereKey($surgery->admission_id)->value('status'), ['admitted', 'active', 'transferred'], true);
            $this->recovery->completeTransfer($recovery->refresh(), $stillInside ? 'ward' : 'discharge', $user);
        });
    }

    private function provider(HistoryContext $context, User $user, string $type): Provider
    {
        return Provider::firstOrCreate(
            ['organization_id' => $context->organization->id, 'user_id' => $user->id, 'provider_type' => $type],
            ['branch_id' => $context->branch->id, 'provider_code' => strtoupper(substr($type, 0, 4)).'-'.$user->id, 'name' => $user->name, 'status' => 'active'],
        );
    }
}
