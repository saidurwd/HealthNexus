<?php

namespace App\Services\Ed;

use App\Events\Ed\EdVisitDispositioned;
use App\Models\Ed\EdConsult;
use App\Models\Ed\EdDisposition;
use App\Models\Ed\EdObservationStay;
use App\Models\Ed\EdProtocolRun;
use App\Models\Ed\EdVisit;
use App\Models\Encounter;
use App\Models\User;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\EncounterLifecycleService;
use App\Services\Icu\IcuAdmissionRequestService;
use App\Services\Ipd\IpdAdmissionRequestService;
use App\Services\Ot\OtSurgeryRequestService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * How a patient leaves the department. Each decision closes the visit exactly once (the unique key on ed_dispositions is the last line of
 * defence), frees the bay, ends an open observation stay (so it is billed), and hands over to the receiving module through its own service.
 */
class EdDispositionService
{
    private const PATH_TO_COMPLETED = ['registered' => 'waiting', 'waiting' => 'in_progress', 'in_progress' => 'completed'];

    public function __construct(
        private readonly EdBayService $bays,
        private readonly EdVisitService $visits,
        private readonly EdObservationService $observation,
        private readonly EdProtocolService $protocols,
        private readonly EdConsultService $consults,
        private readonly EncounterLifecycleService $encounters,
        private readonly EncounterClinicalService $clinical,
        private readonly IpdAdmissionRequestService $ipd,
        private readonly IcuAdmissionRequestService $icu,
        private readonly OtSurgeryRequestService $surgery,
    ) {}

    /** @param array<string, mixed> $data type + the details that type needs (see config ed.dispositions) */
    public function decide(EdVisit $visit, User $by, array $data): EdDisposition
    {
        $type = $data['type'] ?? null;
        $rule = config("ed.dispositions.$type") ?? throw ValidationException::withMessages(['type' => 'Not a valid choice.']);

        foreach ($rule['needs'] as $need) {
            if (blank($data[$need] ?? null) || ($need === 'risks_explained' && ! filter_var($data[$need], FILTER_VALIDATE_BOOLEAN))) {
                throw ValidationException::withMessages([$need => 'This is required for this decision.']);
            }
        }

        return DB::transaction(function () use ($visit, $by, $data, $type) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);
            if (! $locked->isActive() || EdDisposition::query()->where('visit_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['visit' => 'This visit is already closed.']);
            }

            $target = $this->handOver($locked, $by, $type, $data);

            $stay = EdObservationStay::query()->where('visit_id', $locked->id)->whereNull('ended_at')->first();
            $stay && $this->observation->end($stay, $by);
            $locked->refresh();

            EdProtocolRun::query()->where('visit_id', $locked->id)->where('status', EdProtocolRun::ACTIVE)->each(fn ($run) => $this->protocols->cancel($run));
            EdConsult::query()->where('visit_id', $locked->id)->where('status', EdConsult::REQUESTED)->each(fn ($c) => $this->consults->cancel($c));

            $this->bays->release($locked, $by);
            $this->closeEncounter($locked->encounter, $by, $type);

            $disposition = EdDisposition::create([
                'organization_id' => $locked->organization_id, 'visit_id' => $locked->id, 'type' => $type, 'destination' => $data['destination'] ?? null,
                'reason' => $data['reason'] ?? null, 'ambulance_required' => (bool) ($data['ambulance_required'] ?? false),
                'details' => array_filter(['time_of_death' => $data['time_of_death'] ?? null, 'cause' => $data['cause'] ?? null, 'follow_up' => $data['follow_up'] ?? null, 'risks_explained' => isset($data['risks_explained']) ? true : null]),
                'target_type' => $target ? $target::class : null, 'target_id' => $target?->getKey(), 'decided_at' => now(), 'decided_by' => $by->id,
            ]);

            $from = $locked->status;
            $locked->update(['status' => EdVisit::DISPOSITIONED, 'disposition_type' => $type, 'dispositioned_at' => now()]);
            $this->visits->recordStatus($locked, $from, EdVisit::DISPOSITIONED, $by, 'Disposition: '.$type);

            EdVisitDispositioned::dispatch($locked->id, $locked->organization_id, $locked->patient_id);

            return $disposition;
        });
    }

    /** Creates the request in the receiving module through its own service. Returns the created record, when there is one. */
    private function handOver(EdVisit $visit, User $by, string $type, array $data): ?Model
    {
        $base = ['organization_id' => $visit->organization_id, 'branch_id' => $visit->branch_id, 'patient_id' => $visit->patient_id];

        return match ($type) {
            'admit_ipd' => $this->ipd->create($base + ['source_encounter_id' => $visit->encounter_id, 'reason' => $data['reason'], 'priority' => 'emergency'], $by),
            'icu' => $this->icu->create($base + ['encounter_id' => $visit->encounter_id, 'admission_reason' => $data['reason'], 'priority' => 'emergency', 'is_emergency' => true, 'override_reason' => $data['reason']], $by),
            'surgery' => $this->surgery->create($base + ['encounter_id' => $visit->encounter_id, 'indication' => $data['reason'], 'priority' => 'emergency'], $by),
            'transfer', 'referral' => $this->clinical->addReferral($visit->encounter, ['referral_type' => $type, 'referred_to' => $data['destination'], 'reason' => $data['reason'], 'referred_by' => $by->id], $by),
            'death' => $this->recordDeath($visit, $by, $data),
            default => null,
        };
    }

    /** Death: the time and cause are mandatory, and only a user allowed to declare death can do it. The patient banner then shows deceased. */
    private function recordDeath(EdVisit $visit, User $by, array $data): null
    {
        if (! $by->can('ed.death.declare')) {
            throw ValidationException::withMessages(['type' => 'Only a doctor allowed to declare death can record a death.']);
        }
        $at = Carbon::parse($data['time_of_death']);
        if ($at->isFuture() || $at->lt($visit->arrival_at->copy()->subDay())) {
            throw ValidationException::withMessages(['time_of_death' => 'The time of death is not possible.']);
        }

        $visit->patient()->update(['status' => 'deceased', 'deceased_at' => $at]);

        return null;
    }

    /** Walks the encounter to completed (which locks the record); a patient who left before being seen has the encounter cancelled. */
    private function closeEncounter(Encounter $encounter, User $by, string $type): void
    {
        if ($type === 'lwbs' && $this->encounters->canTransition($encounter, 'cancelled')) {
            $this->encounters->moveTo($encounter, 'cancelled', $by, 'Left without being seen');

            return;
        }

        while ($encounter->status !== 'completed' && isset(self::PATH_TO_COMPLETED[$encounter->status])) {
            $encounter = $this->encounters->moveTo($encounter, self::PATH_TO_COMPLETED[$encounter->status], $by, 'ED disposition: '.$type);
        }
    }
}
