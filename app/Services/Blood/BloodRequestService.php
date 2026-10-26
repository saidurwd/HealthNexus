<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodSample;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A clinician's request for blood and the patient's sample. The sample is the safety gate: a wrongly labelled or discordant tube is rejected,
 * and a patient with no group on record needs two separate, agreeing samples before the group is confirmed.
 */
class BloodRequestService
{
    public function __construct(
        private readonly NumberSeriesService $numbers,
        private readonly BloodPolicy $policy,
        private readonly BloodIncidentService $incidents,
        private readonly BloodTestingService $testing,
        private readonly AlertService $alerts,
    ) {}

    /** @param  array{indication?: ?string, required_by?: ?CarbonInterface, replacement_donor_offered?: bool, branch_id?: ?int}  $options */
    public function create(User $by, Patient $patient, string $component, int $units, string $priority, string $sourceModule = 'opd', ?Encounter $encounter = null, array $options = []): BloodRequest
    {
        $this->policy->component($component);
        if (! in_array($priority, $this->policy->get('priorities'), true)) {
            throw ValidationException::withMessages(['priority' => 'Not a known priority.']);
        }
        if ($units < 1 || $units > 20) {
            throw ValidationException::withMessages(['units_requested' => 'Ask for between 1 and 20 units.']);
        }
        if ($encounter && $encounter->patient_id !== $patient->id) {
            throw ValidationException::withMessages(['encounter' => 'That encounter belongs to another patient.']);
        }

        $request = BloodRequest::create([
            'organization_id' => $patient->organization_id, 'branch_id' => $options['branch_id'] ?? $encounter?->branch_id, 'patient_id' => $patient->id, 'encounter_id' => $encounter?->id,
            'request_number' => $this->numbers->next('blood.request', $patient->organization_id, null, prefix: 'BRQ', format: '{PREFIX}-{YEAR}-{SEQ:8}', userId: $by->id),
            'source_module' => $sourceModule, 'component' => $component, 'units_requested' => $units, 'priority' => $priority, 'indication' => $options['indication'] ?? null,
            'replacement_donor_offered' => $options['replacement_donor_offered'] ?? false, 'required_by' => $options['required_by'] ?? null, 'requested_by' => $by->id, 'requested_at' => now(),
        ]);

        if (in_array($priority, ['emergency', 'massive'], true)) {
            $this->alerts->raise(new AlertRequest(
                organizationId: $patient->organization_id, category: 'blood_request_urgent', severity: 'critical', sourceModule: 'bloodbank', patientId: $patient->id,
                message: "Urgent blood request {$request->request_number}: {$units} × ".$this->policy->component($component)['label']." ($priority, from $sourceModule).",
                sourceType: BloodRequest::class, sourceId: $request->id, recipientUserIds: $this->testing->roleUsers($patient->organization_id), dedupKey: "blood-req:{$request->id}", raisedBy: $by->id,
            ));
        }

        return $request;
    }

    public function cancel(BloodRequest $request, User $by, string $reason): BloodRequest
    {
        if (! $request->isOpen()) {
            throw ValidationException::withMessages(['request' => 'This request is already closed.']);
        }
        DB::transaction(function () use ($request, $reason) {
            foreach ($request->reservations()->where('status', 'active')->get() as $reservation) {
                app(BloodIssueService::class)->releaseReservation($reservation, 'Request cancelled');
            }
            $request->update(['status' => 'cancelled', 'cancel_reason' => $reason]);
        });

        return $request->refresh();
    }

    /**
     * @param  array{patient_public_id: string, abo: string, rh: string, collected_at: CarbonInterface, collected_by?: ?int, labelled?: bool, haemolysed?: bool}  $data
     */
    public function receiveSample(BloodRequest $request, User $receivedBy, array $data): BloodSample
    {
        return DB::transaction(function () use ($request, $receivedBy, $data) {
            $locked = BloodRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['request' => 'This request is closed.']);
            }
            $patient = Patient::findOrFail($locked->patient_id);
            $abo = strtoupper($data['abo']);
            $rh = strtolower($data['rh']);

            $rejection = match (true) {
                ($data['patient_public_id'] ?? null) !== $patient->public_id => 'wrong_patient',
                ! ($data['labelled'] ?? true) => 'unlabelled',
                (bool) ($data['haemolysed'] ?? false) => 'haemolysed',
                ! in_array($abo, $this->policy->get('abo'), true) || ! in_array($rh, $this->policy->get('rh'), true) => 'other',
                default => null,
            };
            $confirmed = $this->confirmedGroup($patient);
            if (! $rejection && $confirmed && ($confirmed['abo'] !== $abo || $confirmed['rh'] !== $rh)) {
                $rejection = 'group_discordant';
            }

            $sample = BloodSample::create([
                'organization_id' => $locked->organization_id, 'request_id' => $locked->id, 'patient_id' => $patient->id, 'abo' => $abo, 'rh' => $rh,
                'status' => $rejection ? 'rejected' : 'received', 'rejection_reason' => $rejection, 'collected_at' => $data['collected_at'], 'collected_by' => $data['collected_by'] ?? null,
                'received_at' => now(), 'received_by' => $receivedBy->id, 'expires_at' => $data['collected_at']->copy()->addHours((int) $this->policy->get('sample_validity_hours')),
            ]);

            if ($rejection) {
                $this->incidents->open($locked->organization_id, 'wrong_sample', "Sample rejected on {$locked->request_number}: $rejection.", in_array($rejection, ['wrong_patient', 'group_discordant'], true) ? 'high' : 'moderate', $patient, null, $sample, $receivedBy);

                return $sample;
            }

            if ($confirmed) {
                $sample->update(['status' => 'accepted']);
                $locked->update(['status' => 'sample_received']);

                return $sample->refresh();
            }

            // No confirmed group yet: a second, separate and agreeing sample is needed (when the policy asks for it).
            if (! $this->policy->get('second_sample_required_when_no_group_on_file')) {
                $this->confirm($patient, $abo, $rh, $sample);
                $locked->update(['status' => 'sample_received']);

                return $sample->refresh();
            }

            $first = BloodSample::query()->where('patient_id', $patient->id)->where('status', 'provisional')->where('expires_at', '>', now())->where('id', '!=', $sample->id)->latest('id')->first();
            if (! $first) {
                $sample->update(['status' => 'provisional', 'sequence' => 1]);

                return $sample->refresh();
            }
            if ($first->abo === $abo && $first->rh === $rh && $first->collected_at->ne($sample->collected_at)) {
                $first->update(['status' => 'accepted']);
                $sample->update(['sequence' => 2]);
                $this->confirm($patient, $abo, $rh, $sample);
                $locked->update(['status' => 'sample_received']);
            } else {
                $first->update(['status' => 'rejected', 'rejection_reason' => 'group_discordant']);
                $sample->update(['status' => 'rejected', 'rejection_reason' => 'group_discordant']);
                $this->incidents->open($locked->organization_id, 'wrong_sample', "Two samples for the same patient disagree on {$locked->request_number} — both rejected.", 'high', $patient, null, $sample, $receivedBy);
            }

            return $sample->refresh();
        });
    }

    /** @return array{abo: string, rh: string}|null the group confirmed by an accepted sample, if any */
    public function confirmedGroup(Patient $patient): ?array
    {
        $sample = BloodSample::query()->where('patient_id', $patient->id)->where('status', 'accepted')->latest('id')->first();

        return $sample ? ['abo' => (string) $sample->abo, 'rh' => (string) $sample->rh] : null;
    }

    private function confirm(Patient $patient, string $abo, string $rh, BloodSample $sample): void
    {
        $sample->update(['status' => 'accepted']);
        $patient->update(['blood_group' => $abo.($rh === 'positive' ? '+' : '-'), 'rh_factor' => $rh]);
    }
}
