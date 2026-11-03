<?php

namespace App\Interop\Hl7;

use App\Models\Branch;
use App\Models\Integration\Hl7InboundResult;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Services\EncounterService;
use App\Services\Interop\ExchangeGuard;
use App\Services\Interop\IdMapper;
use App\Services\Interop\InteropException;
use App\Services\Interop\InteropSystemUser;
use App\Services\Interop\MessageService;
use App\Services\Interop\PatientIntakeService;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * What happens to an HL7 v2 message that arrives (S08). Always answers with an ACK: AA the message was accepted and handled, AE we could not handle it but the sender may fix and resend, AR refused for good.
 * Senders are known by the application name they put in MSH-3 (a configured partner); anyone else is refused. A repeated message (same control id) is acknowledged and not done twice.
 * Patient and visit changes go through the domain services; results are parked for a person to review.
 */
class Hl7Inbound
{
    public function __construct(
        private readonly Hl7Builder $builder,
        private readonly MessageService $messages,
        private readonly PatientIntakeService $intake,
        private readonly EncounterService $encounters,
        private readonly InteropSystemUser $system,
        private readonly IdMapper $ids,
        private readonly ExchangeGuard $guard,
    ) {}

    /** @return string the ACK (HL7 text) */
    public function handle(string $raw): string
    {
        try {
            $msg = Hl7Message::parse($raw);
        } catch (Throwable $e) {
            return $this->builder->rawNack('not readable as HL7 v2');
        }
        $partner = $this->partnerFor($msg);
        if ($partner === null) {
            return $this->builder->ack($msg, 'AR', 'unknown sending application');
        }
        $type = $msg->get('MSH-9.1').'^'.$msg->get('MSH-9.2');
        if (! in_array($type, (array) config('interop.hl7.accepted_types'), true)) {
            return $this->builder->ack($msg, 'AR', "message type $type is not accepted");
        }
        [$stored, $duplicate] = $this->messages->receive($partner->organization_id, $partner, 'hl7', $type, $raw, $msg->controlId() ?: hash('sha256', $raw), ['correlation_id' => $msg->controlId() ?: null,
            'provenance' => ['sender' => $msg->get('MSH-3').'@'.$msg->get('MSH-4'), 'version' => $msg->get('MSH-12')]]);
        if ($duplicate) {
            return $this->builder->ack($msg, 'AA', 'duplicate: already received');
        }
        try {
            $note = match ($msg->get('MSH-9.1')) {
                'ADT' => $this->adt($msg, $partner, $stored),
                'ORU' => $this->oru($msg, $partner, $stored),
                default => throw new InteropException('orders by HL7 are not accepted yet', 422, 'not-supported'),
            };
            $stored->update(['status' => 'processed', 'processed_at' => now(), 'error' => null]);

            return $this->builder->ack($msg, 'AA', $note);
        } catch (InteropException $e) {
            $stored->update(['status' => 'rejected', 'error' => mb_substr($e->getMessage(), 0, 500)]);

            return $this->builder->ack($msg, $e->status === 409 ? 'AE' : 'AR', $e->getMessage());
        } catch (Throwable $e) {
            $stored->update(['status' => 'dead', 'dead_at' => now(), 'error' => mb_substr($e->getMessage(), 0, 500)]);
            report($e);

            return $this->builder->ack($msg, 'AE', 'internal error: please resend later');
        }
    }

    private function partnerFor(Hl7Message $msg): ?IntegrationPartner
    {
        $app = $msg->get('MSH-3');
        if ($app === '') {
            return null;
        }

        return IntegrationPartner::query()->where('kind', 'hl7_peer')->where('status', 'active')->whereIn('direction', ['in', 'both'])->get()
            ->first(fn (IntegrationPartner $p) => ($p->settings['sending_application'] ?? $p->code) === $app && (($p->settings['sending_facility'] ?? null) === null || $p->settings['sending_facility'] === $msg->get('MSH-4')));
    }

    /** @return array<string, mixed> patient fields from PID, with the sender's identifiers sorted into ours and theirs */
    private function pid(Hl7Message $msg, IntegrationPartner $partner): array
    {
        $in = ['first_name' => $msg->get('PID-5.2'), 'middle_name' => $msg->get('PID-5.3') ?: null, 'last_name' => $msg->get('PID-5.1'), 'sex' => match ($msg->get('PID-8')) {
            'M' => 'M', 'F' => 'F', 'O' => 'O', default => null
        },
            'phone' => $msg->get('PID-13.1') ?: null, 'address' => $msg->get('PID-11.1') ?: null, 'city' => $msg->get('PID-11.3') ?: null];
        $dob = $msg->get('PID-7');
        $in['date_of_birth'] = preg_match('/^\d{8}/', $dob) ? CarbonImmutable::createFromFormat('Ymd', substr($dob, 0, 8))->toDateString() : null;
        for ($i = 0; $i < $msg->repetitions('PID', 3); $i++) {
            [$value, $authority, $kind] = [$msg->in('PID', 0, 3, 1, $i), $msg->in('PID', 0, 3, 4, $i), $msg->in('PID', 0, 3, 5, $i)];
            if ($value === '') {
                continue;
            }
            if ($kind === 'NI') {
                $in['national_identifier'] = $value;
            } elseif ($authority === 'HN' && in_array($kind, ['MR', ''], true)) {
                $in['mrn'] = $value; // the sender is quoting OUR record number
            } else {
                $in['external_id'] ??= $value;
            }
        }

        return array_filter($in, fn ($v) => $v !== null && $v !== '');
    }

    private function adt(Hl7Message $msg, IntegrationPartner $partner, IntegrationMessage $stored): string
    {
        $event = $msg->get('MSH-9.2');
        $in = $this->pid($msg, $partner);
        if (empty($in['last_name']) && empty($in['mrn']) && empty($in['external_id'])) {
            throw new InteropException('PID has no patient name or identifier', 422, 'required');
        }
        [$patient, $created] = $this->intake->upsert($partner->organization_id, $partner, $in, $event !== 'A03');
        $stored->update(['patient_id' => $patient->id]);
        $this->guard->log($partner, $patient, 'Patient', $this->ids->expose($patient), 'receive', 'allowed');
        $note = $created ? 'patient registered' : 'patient updated';

        $visitNo = $msg->get('PV1-19');
        if ($visitNo !== '' && in_array($event, ['A01', 'A04'], true) && $msg->count('PV1') > 0) {
            if ($this->ids->localFor($partner->code, 'Encounter', $visitNo, $partner->organization_id) === null) {
                $branch = $partner->settings['branch_id'] ?? Branch::query()->where('organization_id', $partner->organization_id)->orderBy('id')->value('id');
                $type = match ($msg->get('PV1-2')) {
                    'I' => 'IPD', 'E' => 'ED', default => 'OPD'
                };
                $encounter = $this->encounters->createEncounter(['organization_id' => $partner->organization_id, 'branch_id' => $branch, 'patient_id' => $patient->id, 'encounter_type' => $type, 'source' => 'integration',
                    'reason_for_visit' => 'Registered by '.$partner->name], $this->system->for($partner->organization_id));
                $this->ids->link($partner->code, 'Encounter', $encounter->id, $visitNo, $partner->organization_id);
                $note .= ', visit created';
            } else {
                $note .= ', visit already known';
            }
        }

        return $note;
    }

    private function oru(Hl7Message $msg, IntegrationPartner $partner, IntegrationMessage $stored): string
    {
        $in = $this->pid($msg, $partner);
        $patient = $this->intake->find($partner->organization_id, $partner, $in);
        if ($patient === null) {
            throw new InteropException('the patient in PID is not known here', 409, 'not-found');
        }
        $stored->update(['patient_id' => $patient->id]);
        $this->guard->log($partner, $patient, 'DiagnosticReport', null, 'receive', 'allowed');
        $n = 0;
        for ($i = 0; $i < $msg->count('OBX'); $i++) {
            $code = $msg->in('OBX', $i, 3, 1);
            if ($code === '') {
                continue;
            }
            $observed = $msg->in('OBX', $i, 14) ?: $msg->in('OBR', 0, 7);
            Hl7InboundResult::create(['organization_id' => $partner->organization_id, 'message_id' => $stored->id, 'patient_id' => $patient->id, 'patient_ref' => $in['external_id'] ?? ($in['mrn'] ?? null),
                'placer_order_no' => $msg->in('OBR', 0, 2) ?: null, 'filler_order_no' => $msg->in('OBR', 0, 3) ?: null, 'code' => mb_substr($code, 0, 60), 'name' => mb_substr($msg->in('OBX', $i, 3, 2), 0, 200) ?: null,
                'value' => mb_substr($msg->in('OBX', $i, 5), 0, 500) ?: null, 'unit' => mb_substr($msg->in('OBX', $i, 6), 0, 40) ?: null, 'reference_range' => mb_substr($msg->in('OBX', $i, 7), 0, 80) ?: null,
                'abnormal_flag' => mb_substr($msg->in('OBX', $i, 8), 0, 10) ?: null, 'observed_at' => preg_match('/^\d{8}/', $observed) ? CarbonImmutable::createFromFormat('YmdHis', str_pad(substr($observed, 0, 14), 14, '0'))->toDateTimeString() : null]);
            $n++;
        }
        if ($n === 0) {
            throw new InteropException('the report has no results (OBX)', 422, 'required');
        }

        return "$n result(s) kept for review";
    }
}
