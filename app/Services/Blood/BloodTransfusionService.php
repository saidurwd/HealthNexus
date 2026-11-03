<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodIssue;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodTransfusion;
use App\Models\Blood\BloodTransfusionReaction;
use App\Models\Blood\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use App\Services\Clinical\Observations\ObservationData;
use App\Services\Clinical\Observations\ObservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * At the bedside: a two-person check of the wristband and the bag, the transfusion record with observations, and reactions. A reaction stops the
 * transfusion, alerts the blood bank and the doctor, opens an incident, and (for serious ones) holds the other bags from the same donation.
 */
class BloodTransfusionService
{
    private const VITALS = ['temperature' => 'body_temperature', 'pulse' => 'heart_rate', 'respiratory_rate' => 'respiratory_rate', 'oxygen_saturation' => 'oxygen_saturation', 'systolic' => 'systolic_bp', 'diastolic' => 'diastolic_bp'];

    private const SERIOUS = ['acute_haemolytic', 'anaphylaxis', 'septic', 'trali'];

    public function __construct(
        private readonly BloodPolicy $policy,
        private readonly BloodUnitService $units,
        private readonly BloodIncidentService $incidents,
        private readonly BloodTestingService $testing,
        private readonly ObservationService $observations,
        private readonly AlertService $alerts,
    ) {}

    /** @param  array<string, float|int|null>  $baseline  optional starting observations */
    public function start(BloodIssue $issue, User $nurse1, User $nurse2, string $wristbandScan, string $bagScan, ?string $overrideReason = null, array $baseline = []): BloodTransfusion
    {
        if ($nurse1->id === $nurse2->id) {
            throw ValidationException::withMessages(['verifier' => 'Two different people must do the bedside check.']);
        }

        return DB::transaction(function () use ($issue, $nurse1, $nurse2, $wristbandScan, $bagScan, $overrideReason, $baseline) {
            $lockedIssue = BloodIssue::query()->lockForUpdate()->findOrFail($issue->id);
            $unit = BloodUnit::query()->lockForUpdate()->findOrFail($lockedIssue->unit_id);
            $patient = Patient::findOrFail($lockedIssue->patient_id);
            if ($lockedIssue->status !== 'issued' || $unit->status !== BloodUnit::ISSUED) {
                throw ValidationException::withMessages(['issue' => 'This unit is not waiting to be transfused.']);
            }
            if ($unit->isExpired()) {
                throw ValidationException::withMessages(['unit' => 'This unit has expired.']);
            }

            $patientOk = in_array(trim($wristbandScan), array_filter([$patient->public_id, $patient->enterprise_patient_no]), true);
            $bagOk = trim($bagScan) === $unit->unit_number;
            $open = BloodIncident::query()->where('patient_id', $patient->id)->where('incident_type', 'transfusion_reaction')->where('status', '!=', 'closed')->exists();
            $problems = array_values(array_filter([! $patientOk ? 'wristband does not match the patient' : null, ! $bagOk ? 'bag does not match the issued unit' : null, $open ? 'an earlier reaction for this patient is still open' : null]));

            if ($problems !== [] && strlen(trim((string) $overrideReason)) < 5) {
                throw ValidationException::withMessages(['verification' => 'Bedside check failed: '.implode('; ', $problems).'. Do not start. A reasoned override is the only way to continue.']);
            }

            $transfusion = BloodTransfusion::create([
                'organization_id' => $lockedIssue->organization_id, 'issue_id' => $lockedIssue->id, 'patient_id' => $patient->id, 'encounter_id' => BloodRequest::whereKey($lockedIssue->request_id)->value('encounter_id'),
                'status' => 'in_progress', 'verified_by_1' => $nurse1->id, 'verified_by_2' => $nurse2->id, 'verification_override' => $problems !== [], 'override_reason' => $problems !== [] ? $overrideReason : null, 'started_at' => now(),
            ]);
            $lockedIssue->update(['status' => 'transfusing']);
            $this->units->event($unit, 'transfusion_started', $nurse1, $problems ? 'Started with override' : 'Two-person check passed', $transfusion);

            if ($problems !== []) {
                $this->incidents->open($lockedIssue->organization_id, 'verification_override', 'Bedside check overridden ('.implode('; ', $problems)."): $overrideReason", 'high', $patient, $unit, $transfusion, $nurse1);
            }
            $baseline && $this->recordObservations($transfusion, $nurse1, $baseline);

            return $transfusion;
        });
    }

    /** @param  array<string, float|int|null>  $vitals */
    public function recordObservations(BloodTransfusion $transfusion, User $by, array $vitals): void
    {
        if ($transfusion->status !== 'in_progress') {
            throw ValidationException::withMessages(['transfusion' => 'This transfusion is no longer running.']);
        }
        foreach (self::VITALS as $field => $key) {
            if (filled($vitals[$field] ?? null)) {
                $this->observations->record(new ObservationData(
                    organizationId: $transfusion->organization_id, patientId: $transfusion->patient_id, contextType: 'transfusion', key: $key, valueNumber: (float) $vitals[$field],
                    encounterId: $transfusion->encounter_id, contextId: $transfusion->id, sourceModule: 'bloodbank',
                ), $by);
            }
        }
    }

    public function complete(BloodTransfusion $transfusion, User $by, int $volumeGivenMl): BloodTransfusion
    {
        return DB::transaction(function () use ($transfusion, $by, $volumeGivenMl) {
            $locked = BloodTransfusion::query()->lockForUpdate()->findOrFail($transfusion->id);
            if ($locked->status !== 'in_progress') {
                throw ValidationException::withMessages(['transfusion' => 'Only a running transfusion can be completed.']);
            }
            $issue = BloodIssue::findOrFail($locked->issue_id);
            $unit = BloodUnit::findOrFail($issue->unit_id);
            $locked->update(['status' => 'completed', 'volume_given_ml' => $volumeGivenMl, 'ended_at' => now()]);
            $issue->update(['status' => 'transfused']);
            $this->units->move($unit, BloodUnit::TRANSFUSED, $by, "Transfused: {$volumeGivenMl} ml", $locked);
            $this->closeRequestIfDone($issue->request_id);
            if ($locked->started_at->diffInHours(now(), true) > (int) $this->policy->get('transfusion')['max_hours']) {
                $this->incidents->open($locked->organization_id, 'near_miss', "Transfusion of {$unit->unit_number} ran longer than ".$this->policy->get('transfusion')['max_hours'].' hours.', 'moderate', Patient::find($locked->patient_id), $unit, $locked, $by, false);
            }

            return $locked->refresh();
        });
    }

    /** @param  list<string>  $symptoms */
    public function reportReaction(BloodTransfusion $transfusion, User $by, string $type, string $severity, array $symptoms = [], ?string $actions = null): BloodTransfusionReaction
    {
        if (! in_array($type, $this->policy->get('reaction_types'), true) || ! in_array($severity, $this->policy->get('reaction_severities'), true)) {
            throw ValidationException::withMessages(['reaction' => 'Not a known type or severity of reaction.']);
        }

        return DB::transaction(function () use ($transfusion, $by, $type, $severity, $symptoms, $actions) {
            $locked = BloodTransfusion::query()->lockForUpdate()->findOrFail($transfusion->id);
            $issue = BloodIssue::findOrFail($locked->issue_id);
            $unit = BloodUnit::findOrFail($issue->unit_id);
            $patient = Patient::findOrFail($locked->patient_id);

            if ($locked->status === 'in_progress') {
                $locked->update(['status' => 'stopped_reaction', 'stop_reason' => "Reaction: $type", 'ended_at' => now()]);
                $issue->update(['status' => 'transfused']);
                $this->units->move($unit, BloodUnit::TRANSFUSED, $by, "Stopped for a reaction ($type)", $locked);
                $this->closeRequestIfDone($issue->request_id);
            }

            $reaction = BloodTransfusionReaction::create([
                'organization_id' => $locked->organization_id, 'transfusion_id' => $locked->id, 'patient_id' => $patient->id, 'reaction_type' => $type, 'severity' => $severity,
                'symptoms' => $symptoms, 'actions_taken' => $actions, 'detected_at' => now(), 'reported_by' => $by->id,
            ]);

            $serious = $severity === 'severe' || in_array($type, self::SERIOUS, true);
            $request = BloodRequest::findOrFail($issue->request_id);
            $this->incidents->open($locked->organization_id, 'transfusion_reaction', "Transfusion reaction ($type, $severity) with {$unit->unit_number}.", $serious ? 'critical' : 'high', $patient, $unit, $reaction, $by, false);
            $this->alerts->raise(new AlertRequest(
                organizationId: $locked->organization_id, category: 'blood_transfusion_reaction', severity: 'critical', sourceModule: 'bloodbank', patientId: $patient->id,
                message: "Transfusion reaction ($type, $severity): {$patient->full_name}, unit {$unit->unit_number}. Transfusion stopped.", sourceType: BloodTransfusionReaction::class, sourceId: $reaction->id,
                recipientUserIds: array_values(array_unique(array_merge($this->testing->roleUsers($locked->organization_id), array_filter([$request->requested_by])))), dedupKey: "blood-reaction:{$reaction->id}", raisedBy: $by->id,
            ));

            if ($serious && $unit->donation_id) {
                foreach (BloodUnit::query()->where('donation_id', $unit->donation_id)->whereKeyNot($unit->id)->whereIn('status', [BloodUnit::AVAILABLE, BloodUnit::RESERVED, BloodUnit::QUARANTINED])->get() as $sibling) {
                    $this->units->move($sibling, BloodUnit::BLOCKED, $by, 'Held: serious reaction to another bag of the same donation', $reaction, ['blocked_reason' => 'reaction_review']);
                }
            }

            return $reaction;
        });
    }

    private function closeRequestIfDone(int $requestId): void
    {
        $request = BloodRequest::findOrFail($requestId);
        $done = $request->issues()->where('status', 'transfused')->count();
        if ($done >= $request->units_requested && $request->issues()->whereIn('status', ['issued', 'transfusing'])->doesntExist()) {
            $request->update(['status' => 'completed']);
        }
    }
}
