<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Ipd\IpdAdmission;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEpisode;
use App\Models\Pharmacy\PharmacyMedication;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Nursing\MedicationAdministrationService;
use App\Services\Nursing\NursingAssignmentService;
use App\Services\Nursing\NursingFluidBalanceService;
use App\Services\Nursing\NursingNoteService;
use App\Services\Nursing\NursingObservationService;
use App\Services\Nursing\NursingPainAssessmentService;
use App\Services\Nursing\NursingTaskService;
use App\Services\Nursing\NursingVitalService;
use Carbon\CarbonImmutable;

/** For every inpatient stay (the episodes IPD opened): vitals three times a day, observations, tasks, medicine rounds, fluid balance, pain scores and notes. */
class NursingHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly NursingAssignmentService $assignments,
        private readonly NursingVitalService $vitals,
        private readonly NursingObservationService $observations,
        private readonly MedicationAdministrationService $mar,
        private readonly NursingTaskService $tasks,
        private readonly NursingNoteService $notes,
        private readonly NursingFluidBalanceService $fluids,
        private readonly NursingPainAssessmentService $pain,
    ) {}

    public function name(): string
    {
        return 'Nursing';
    }

    public function module(): string
    {
        return 'nursing';
    }

    public function run(HistoryContext $context): string
    {
        $nurses = $context->staff('staff_nurse');
        $medicines = PharmacyMedication::where('organization_id', $context->organization->id)->where('is_active', true)->limit(12)->get();
        $counts = ['episodes' => 0, 'vitals' => 0, 'doses' => 0, 'tasks' => 0, 'notes' => 0, 'missed_doses' => 0];

        NursingEpisode::query()->where('organization_id', $context->organization->id)->with('admission')->orderBy('id')->each(function (NursingEpisode $episode) use ($context, $nurses, $medicines, &$counts) {
            $counts['episodes']++;
            $admission = IpdAdmission::findOrFail($episode->admission_id);
            $first = CarbonImmutable::parse($admission->admitted_at);
            $last = ($admission->actual_discharge_date ? CarbonImmutable::parse($admission->actual_discharge_date) : $context->to->addDay())->min($context->to->addDay());
            $primary = $context->pick($nurses);

            $context->at($first, fn () => $this->assignments->assign($episode, ['nurse_id' => $primary->id, 'assignment_type' => NursingAssignment::TYPE_PATIENT, 'ward_id' => null, 'bed_id' => null], $context->actor));

            for ($day = $first->startOfDay(); $day->lt($last); $day = $day->addDay()) {
                foreach ([[8, 'morning'], [14, 'evening'], [20, 'night']] as [$hour, $shift]) {
                    $at = $day->setTime($hour, 0);
                    if ($at->lt($first) || $at->gt($context->to->addDay())) {
                        continue;
                    }
                    $nurse = $context->chance(70) ? $primary : $context->pick($nurses);
                    $context->at($at, function () use ($context, $episode, $nurse, $medicines, &$counts, $hour) {
                        $this->vitals->record($episode, [
                            'temperature' => round(36.4 + mt_rand(0, 25) / 10, 1), 'systolic' => mt_rand(100, 150), 'diastolic' => mt_rand(62, 95), 'pulse_rate' => mt_rand(64, 100),
                            'respiratory_rate' => mt_rand(13, 22), 'oxygen_saturation' => mt_rand(94, 100), 'weight' => null, 'height' => null,
                        ], $nurse);
                        $counts['vitals']++;
                        $this->observations->record($episode, ['observation_type' => 'blood_glucose', 'value' => (string) mt_rand(80, 220), 'unit' => 'mg/dL'], $nurse);

                        if ($medicines->isNotEmpty()) {
                            $medicine = $context->pick($medicines);
                            $mar = $this->mar->createAdHoc($episode, ['medication_id' => $medicine->id, 'dose' => '1', 'dose_unit' => 'tablet', 'route' => 'oral', 'scheduled_at' => now()], $nurse);
                            $roll = mt_rand(1, 100);
                            if ($roll <= 92) {
                                $this->mar->administer($mar, $nurse, ['patient' => true, 'dose' => true, 'route' => true, 'time' => true]);
                                $counts['doses']++;
                            } elseif ($roll <= 96) {
                                $this->mar->hold($mar, $nurse, 'Patient nil by mouth');
                                $counts['missed_doses']++;
                            } else {
                                $this->mar->refuse($mar, $nurse, 'Patient refused');
                                $counts['missed_doses']++;
                            }
                        }

                        $task = $this->tasks->create($episode, ['task_type' => $context->pick(['turn_patient', 'wound_care', 'mouth_care', 'ambulate', 'check_iv_site']), 'due_at' => now()->addMinutes(30), 'priority' => 'routine', 'assigned_nurse_id' => $nurse->id]);
                        if ($context->chance(92)) {
                            $this->tasks->start($task);
                            $this->tasks->complete($task->refresh(), $nurse, 'done');
                            $counts['tasks']++;
                        }

                        if ($hour === 14) {
                            $this->fluids->recordIntake($episode, ['category' => 'oral', 'amount' => mt_rand(400, 900), 'unit' => 'ml', 'recorded_at' => now()], $nurse);
                            $this->fluids->recordOutput($episode, ['category' => 'urine', 'amount' => mt_rand(300, 800), 'unit' => 'ml', 'recorded_at' => now()], $nurse);
                        }
                        if ($hour === 8 && $context->chance(40)) {
                            $this->pain->record($episode, ['scale_type' => 'numeric', 'score' => mt_rand(0, 7), 'location' => $context->pick(['abdomen', 'chest', 'back', 'head', 'leg']), 'assessed_at' => now()], $nurse);
                        }
                        if ($hour === 20) {
                            $note = $this->notes->create($episode, ['note_type' => 'progress', 'content' => 'Patient comfortable, vitals stable, medicines given as ordered. Family informed.'], $nurse);
                            $this->notes->finalize($note, $nurse);
                            $counts['notes']++;
                        }
                    });
                }
            }
        });

        return sprintf('%d inpatient stays: %d vital sets, %d doses given (%d held/refused), %d tasks done, %d nursing notes', $counts['episodes'], $counts['vitals'], $counts['doses'], $counts['missed_doses'], $counts['tasks'], $counts['notes']);
    }
}
