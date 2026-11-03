<?php

namespace App\Services\Vital;

use App\Models\Patient;
use App\Models\User;
use App\Models\Vital\VrBirthNotification;
use App\Services\AuditLogger;
use App\Services\Geo\GeoService;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Birth notification. The hospital writes down the birth (draft), a second person checks the details and gives the family the notification (issued), and later the number the registrar gave is recorded (registered). The national
 * birth certificate is the registrar's, not ours. An issued record is never edited: it is cancelled with a reason and written again.
 */
class BirthService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly VitalPolicy $policy, private readonly GeoService $geo, private readonly AuditLogger $audit) {}

    /**
     * @param  array<string,mixed>  $d
     */
    public function draft(Patient $mother, array $d, User $by): VrBirthNotification
    {
        $d = $this->clean($mother, $d);

        return DB::transaction(function () use ($mother, $d, $by) {
            $n = VrBirthNotification::create($d + [
                'organization_id' => $mother->organization_id, 'mother_patient_id' => $mother->id, 'created_by' => $by->id, 'status' => 'draft',
                'number' => $this->numbers->next('vital.birth', (int) $mother->organization_id, prefix: 'BN', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id),
            ]);
            $this->audit->log('CREATE', VrBirthNotification::class, $n->id, null, ['number' => $n->number, 'outcome' => $n->outcome]);

            return $n;
        });
    }

    /** The second person checks the details and the notification is given to the family. Not the person who wrote it. */
    public function issue(VrBirthNotification $n, User $verifier, string $issuedTo): VrBirthNotification
    {
        return DB::transaction(function () use ($n, $verifier, $issuedTo) {
            $n = VrBirthNotification::query()->lockForUpdate()->findOrFail($n->id);
            if ($n->status !== 'draft') {
                throw ValidationException::withMessages(['status' => __('Only a draft can be issued.')]);
            }
            if ($n->created_by === $verifier->id) {
                throw ValidationException::withMessages(['status' => __('A second person must check the details and issue it.')]);
            }
            if (trim($issuedTo) === '') {
                throw ValidationException::withMessages(['issued_to' => __('Say who the notification is given to.')]);
            }
            $n->update(['status' => 'issued', 'verified_by' => $verifier->id, 'issued_at' => now(), 'issued_to' => mb_substr(trim($issuedTo), 0, 150)]);
            $this->audit->log('UPDATE', VrBirthNotification::class, $n->id, ['status' => 'draft'], ['status' => 'issued', 'issued_to' => $n->issued_to]);

            return $n;
        });
    }

    /** The registrar's number, recorded once the family has registered the birth. */
    public function register(VrBirthNotification $n, string $reference, User $by): VrBirthNotification
    {
        $reference = trim($reference);
        if ($n->status !== 'issued' || $reference === '' || mb_strlen($reference) > 30) {
            throw ValidationException::withMessages(['registrar_reference' => __('Give the registrar\'s number for a notification that has been issued.')]);
        }
        $n->update(['status' => 'registered', 'registrar_reference' => $reference, 'registered_at' => now()]);
        $this->audit->log('UPDATE', VrBirthNotification::class, $n->id, ['status' => 'issued'], ['status' => 'registered', 'registrar_reference' => $reference]);

        return $n;
    }

    public function cancel(VrBirthNotification $n, string $reason, User $by): VrBirthNotification
    {
        $reason = trim($reason);
        if ($n->status === 'cancelled' || mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['cancel_reason' => __('Give the reason, in a few words.')]);
        }
        $before = $n->status;
        $n->update(['status' => 'cancelled', 'cancel_reason' => mb_substr($reason, 0, 255), 'cancelled_by' => $by->id, 'cancelled_at' => now()]);
        $this->audit->log('UPDATE', VrBirthNotification::class, $n->id, ['status' => $before], ['status' => 'cancelled', 'cancel_reason' => $n->cancel_reason]);

        return $n;
    }

    /** Notifications issued but not registered after the follow-up period, oldest first. @return \Illuminate\Support\Collection<int, VrBirthNotification> */
    public function overdueForRegistration(int $organizationId): Collection
    {
        return VrBirthNotification::query()->where('organization_id', $organizationId)->where('status', 'issued')->where('issued_at', '<=', now()->subDays($this->policy->int('registration_followup_days', 45)))->with('mother:id,first_name,last_name,enterprise_patient_no')->orderBy('issued_at')->get();
    }

    /**
     * @param  array<string,mixed>  $d
     * @return array<string,mixed>
     */
    private function clean(Patient $mother, array $d): array
    {
        if (in_array(strtolower((string) $mother->sex), ['m', 'male'], true)) {
            throw ValidationException::withMessages(['mother_patient_id' => __('The mother must be a female patient.')]);
        }
        $born = ! empty($d['born_at']) ? CarbonImmutable::parse((string) $d['born_at']) : null;
        if ($born === null || $born->isFuture()) {
            throw ValidationException::withMessages(['born_at' => __('Give the date and time of birth; it cannot be in the future.')]);
        }
        $outcome = $d['outcome'] ?? 'live_birth';
        if ($outcome === 'stillbirth' && isset($d['gestation_weeks']) && $d['gestation_weeks'] !== '' && (int) $d['gestation_weeks'] < $this->policy->int('stillbirth_from_weeks', 28)) {
            throw ValidationException::withMessages(['gestation_weeks' => __('Under :n weeks is not recorded as a stillbirth here.', ['n' => $this->policy->int('stillbirth_from_weeks', 28)])]);
        }
        $places = $this->geo->resolve($d);
        $keep = ['child_patient_id', 'outcome', 'child_name', 'sex', 'born_at', 'place_of_birth', 'plurality', 'birth_order', 'weight_g', 'gestation_weeks', 'delivery_type', 'father_name', 'father_nid', 'informant_name', 'informant_relation', 'attendant_id', 'attendant_name', 'village_area', 'branch_id'];

        return array_filter(array_intersect_key($d, array_flip($keep)), fn ($v) => $v !== '' && $v !== null) + $places + ['plurality' => 1, 'birth_order' => 1, 'outcome' => $outcome];
    }
}
